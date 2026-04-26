# 0009 — Multi-LLM gateway with provider abstraction

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

The M6 scope expansion requires AI-driven features — image-based item registration (vision models infer title / category / specs), keyword and similarity search (embeddings + chat-style ranking), and ISBN/barcode enrichment (LLM resolves ambiguous codes against catalogue text). Operators expect to use **OpenAI**, **Google Gemini**, and **Anthropic Claude** interchangeably — sometimes for compliance reasons (data residency), sometimes for cost, sometimes because a particular model is best at a given task.

We need a gateway that:

1. lets the application call **one interface** for chat, vision, and embeddings — call sites shouldn't branch on which vendor is configured;
2. allows **per-feature provider selection** (e.g. "use Gemini for vision but Claude for chat");
3. handles **failover** when a primary provider is rate-limited or down;
4. keeps API keys out of git (encrypted in `system_setting` or `.env`); and
5. ships a **null provider** so contributors can run the suite without any cloud key.

## Decision Drivers

- **Self-hosted reality** — operators deploy SASO on their own infrastructure and choose their own AI vendor. The application must not assume a single provider.
- **Compliance** — Japanese enterprise operators frequently need data-residency guarantees that force one vendor (Gemini in JP, Azure-hosted OpenAI, …).
- **Cost** — embeddings are cheap on OpenAI's smaller models; vision is cheaper on Gemini Flash; reasoning is currently best on Claude. Per-feature routing matters.
- **Compliance with ADR 0001** — the gateway lives in `Infrastructure/`; domain code depends on a domain-level interface (`AiAssistant`).

## Considered Options

### Option A — Pick one vendor (e.g. OpenAI only)

* (+) Simplest scope.
* (−) Operators on Gemini-only or Azure-only stacks cannot use the AI features.
* (−) No failover.

### Option B — Direct SDK calls scattered through the codebase

* (+) Zero abstraction overhead.
* (−) Every new feature reinvents prompt formatting, retry policy, error mapping. Switching vendors becomes a multi-PR refactor.

### Option C — Provider-abstracted gateway: `AiAssistant` interface + per-vendor adapters + feature-keyed provider selection

A single `Saso\Domain\Ai\AiAssistant` interface exposes `chatComplete()`, `extractStructured()`, `embed()`, and `describeImage()`. Concrete implementations: `OpenAiAssistant`, `GeminiAssistant`, `ClaudeAssistant`, plus `NullAssistant` for tests / no-key environments. A `Saso\Infrastructure\Ai\AssistantRouter` reads `system_setting` rows like `ai.provider.vision = gemini`, `ai.provider.embedding = openai`, `ai.provider.chat = claude` and dispatches per call.

API keys live in `system_setting` rows of type `secret`, encrypted via `SecretEncryptor` (M3-E). The default value is `null`; AI features short-circuit to a "AI unavailable" response when no key is configured for the feature's provider.

* (+) Operators pick per-feature provider from the admin UI.
* (+) Failover is a wrapper decorator — `FallbackChainAssistant(primary, secondary)`.
* (+) Tests inject `NullAssistant` (deterministic responses) without touching external services.
* (−) Three SDK adapters to maintain. We mitigate by using the official PHP/HTTP libraries for each (`openai-php/client`, the Gemini REST API, `anthropic-ai/anthropic-php`).

## Decision Outcome

**Chosen option: C — `AiAssistant` interface with provider-abstracted adapters and feature-keyed routing.**

### Domain shape

```php
namespace Saso\Domain\Ai;

interface AiAssistant
{
    public function chatComplete(ChatRequest $req): ChatResponse;
    public function extractStructured(StructuredExtractionRequest $req): StructuredExtractionResponse;
    public function embed(EmbeddingRequest $req): EmbeddingResponse;
    public function describeImage(ImageRequest $req): ImageDescriptionResponse;
}
```

`ChatRequest` carries a `messages` array, `temperature`, `max_tokens`, and an optional `response_format` (`text` / `json` / `json_schema`). `EmbeddingRequest` takes a list of strings or image bytes plus a `task` hint (`retrieval.query` / `retrieval.passage` / `clustering`) — providers that support it use it.

### Provider selection

`system_setting` rows under the `ai.*` prefix:

| Key | Type | Purpose |
|---|---|---|
| `ai.provider.chat` | string | `openai` \| `gemini` \| `claude` \| `null` |
| `ai.provider.embedding` | string | as above |
| `ai.provider.vision` | string | as above |
| `ai.openai.api_key` | secret | OpenAI API key |
| `ai.openai.model.chat` | string | e.g. `gpt-4.1-mini` |
| `ai.openai.model.embedding` | string | e.g. `text-embedding-3-large` |
| `ai.gemini.api_key` | secret | Google AI API key |
| `ai.gemini.model.*` | string | per-task model id |
| `ai.claude.api_key` | secret | Anthropic API key |
| `ai.claude.model.*` | string | per-task model id |

The `AssistantRouter` reads these on first use and caches for the request.

### Failover

`Saso\Infrastructure\Ai\FallbackChainAssistant` wraps an ordered list of `AiAssistant`s. If the primary throws a transient `AiUpstreamException` (5xx, 429, network), the chain tries the next. Configuration is declarative in `system_setting`: `ai.fallback.chat = claude,openai` means "primary Claude, fall back to OpenAI".

### Error codes

New range:

```
SASO-AI-8001  Provider not configured (no API key)
SASO-AI-8002  Provider rate-limited (429)
SASO-AI-8003  Provider response malformed (could not parse expected JSON)
SASO-AI-8004  Provider context window exceeded
SASO-AI-8005  Content policy violation (provider refused)
```

### Lockout safety

`SAFE_MODE=true` in `.env` (already used by the Pluggable IdP per ADR 0003) also disables every AI feature and falls back to `NullAssistant` regardless of `system_setting` state. A misconfigured AI key cannot break the inventory UI.

## Consequences

* All AI features go through `AiAssistant`. Call sites stay vendor-agnostic.
* Operators flip vendor via the admin UI; no redeploys.
* Three SDK dependencies arrive in M6 (`openai-php/client`, an HTTP client for Gemini, `anthropic-ai/anthropic-php`).
* The contract surface is small enough that we accept the maintenance cost of three adapters in exchange for vendor independence.
* `NullAssistant` keeps the test suite hermetic — no cloud calls in CI.
* This ADR does not mandate a vector DB; embeddings produced here flow into whatever vector tier ADR 0010 picks.
