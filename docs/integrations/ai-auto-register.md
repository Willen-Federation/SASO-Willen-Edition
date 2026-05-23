# AI Auto-Registration Integration Guide

> **Status:** Shipped in PR [#235](https://github.com/Willen-Federation/SASO-Willen-Edition/pull/235). Gated by feature flag `ai.auto_register` (default off).
>
> **Audience:** Mobile app developers, server-to-server integrators, MCP tool consumers.
>
> **OpenAPI fragment:** [`config/openapi-diff-ai-auto-register.yaml`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi-diff-ai-auto-register.yaml) — feed this to your SDK generator if you only need the new surface.

The auto-register mode is a turnkey variant of the existing `POST /items/drafts` flow that finishes registration in a single round trip. The server runs JAN/ISBN lookups, AI vision extraction (with iterative re-prompting for missing fields), category resolution, and the final `INSERT INTO item` without a manual confirmation step.

## When to use it

| Use case | Use this endpoint? |
|---|---|
| Operator wants the AI to fully take over registration | ✅ Yes |
| Bulk image upload from a warehouse scanner | ✅ Yes |
| AI assistant (e.g. Claude via MCP) registering items autonomously | ✅ Yes — via the `auto_register_item` MCP tool |
| Operator wants to review the AI output before commit | ❌ Stick with `POST /items/drafts` |
| Strict data-integrity environments where AI guesses are unacceptable | ❌ Stick with `POST /items/drafts` |

The legacy `POST /items/drafts` endpoint is untouched and remains the default for review-first workflows. The new endpoint is an opt-in mode for clients that explicitly want auto-promotion.

## Prerequisites

1. **Bearer token with `items:write` scope.** Issued by `POST /api/v1/mobile/connect` after a QR pairing (mobile) or by an operator session (server-side integration).
2. **`ai.auto_register` feature flag enabled.** Toggle via the admin UI or `PATCH /api/v1/feature-flags/ai.auto_register` with `{"enabled": true}`. When disabled the worker silently falls back to the legacy draft-ready flow — uploads are never lost.
3. **AI provider configured.** At least one of `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, `GEMINI_API_KEY` must be set (env or `system_setting`). If none is configured the pipeline degrades to ISBN/JAN lookups only — auto-register will fail with `error_detail` mentioning `item_name unresolved` for images with no recognisable barcode.
4. **At least one category exists.** The category resolver falls back to the first root category alphabetically. Empty `category` tables cause promotion to fail with `no category available — seed at least one category row.`

## REST: `POST /api/v1/items/auto-register`

### Request

```http
POST /api/v1/items/auto-register HTTP/1.1
Host: saso.sksl.jp
Authorization: Bearer eyJhbGciOi...
Content-Type: multipart/form-data; boundary=----saso-boundary

------saso-boundary
Content-Disposition: form-data; name="image"; filename="product.jpg"
Content-Type: image/jpeg

<binary jpeg bytes>
------saso-boundary
Content-Disposition: form-data; name="barcode_hint"

4901234567890
------saso-boundary--
```

All form fields except `image` are optional hints. Anything you supply here is treated as a **user-protected** field and will not be overwritten by the AI vision step.

| Field | Type | Notes |
|---|---|---|
| `image` | binary | Required. jpeg / png / webp / gif. Max 20 MB. MIME type is verified from the file bytes, not the declared header. |
| `item_name` | string | Optional name hint. |
| `jan_code` | string | Optional JAN/EAN-13. Seeds the JAN lookup step. |
| `isbn` | string | Optional ISBN-13. Seeds the ISBN lookup step. |
| `price` | string | Optional price hint (numeric string). |
| `barcode_hint` | string | Optional barcode string used by both ISBN and JAN lookup steps. |

### Response (202)

```http
HTTP/1.1 202 Accepted
Content-Type: application/json; charset=utf-8

{
  "draft_id": 42,
  "status": "queued",
  "auto_register": true
}
```

The HTTP layer returns immediately. The actual `item.id` is observable later through one of:

- Watching `GET /api/v1/items` (the new row appears with the AI-resolved name and category).
- Polling `GET /api/v1/items/drafts/{draft_id}` once that read endpoint ships in a future PR.
- Server-side log lines tagged `ProcessItemDraft: auto-register promoted draft` carry both `draft_id` and `item_id`.

### Errors

All failure responses follow [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807) with the SASO `code` + `traceId` extensions.

| Code | HTTP | Meaning | Client action |
|---|---|---|---|
| `SASO-DRAFT-4001` | 400 | `image` field missing | Reupload with the multipart `image` part. |
| `SASO-DRAFT-4002` | 400 | Upload transport error | Retry or surface as upload failure. |
| `SASO-DRAFT-4003` | 400 | Payload > 20 MB | Compress / resize. |
| `SASO-DRAFT-4004` | 400 | Unsupported MIME type | Convert to jpeg / png / webp / gif. |
| `SASO-MOBILE-2001` | 401 | Authorization header missing or not Bearer | Re-pair the device. |
| `SASO-MOBILE-2002` | 401 | Invalid / expired Bearer token | Refresh via `/api/v1/mobile/token/refresh`, then re-pair if that fails. |
| `SASO-INFRA-9000` | 500 | Uncaught server error | Retry with backoff; quote `traceId` in support tickets. |

Failures that happen **inside the worker** (after the 202 response has gone out) surface as `item_draft.status = failed` rather than as HTTP errors. Watch the draft row's `error_detail`:

| `error_detail` substring | Cause |
|---|---|
| `item_name could not be resolved by lookups or AI` | AI returned nothing usable and barcode lookups found no name. |
| `no category available — seed at least one category row` | Fresh install with an empty `category` table. |
| `insert failed — <SQL message>` | Database-level error during the final INSERT (FK violation, deadlock, …). |

### Sequence

```
client                       /items/auto-register             ProcessItemDraftHandler            AI / lookups
  |                                  |                                |                                |
  | --- POST image + hints --->      |                                |                                |
  |                                  | INSERT item_draft               |                                |
  |                                  | (auto_register=1, queued)       |                                |
  |                                  | dispatch(ProcessItemDraft)      |                                |
  | <-- 202 Accepted -----           |                                 |                                |
  |     {draft_id, status: queued}   |                                 |                                |
  |                                  |                                 | markProcessing()               |
  |                                  |                                 | AutoRegisterPipeline.run() ----+
  |                                  |                                 |                                |
  |                                  |                                 |  isbnLookup, janLookup --------+--> OpenLibrary/OpenFoodFacts
  |                                  |                                 |  aiVision.run() (attempt 1) ---+--> Claude / OpenAI / Gemini
  |                                  |                                 |  if missing TARGET_KEYS:       |
  |                                  |                                 |    aiVision.runForFields(...)  +--> AI (schema subset, max 3 calls)
  |                                  |                                 |  keywordLookup ----------------+--> OpenBD / OpenLibrary
  |                                  |                                 |                                |
  |                                  |                                 | CategoryHintResolver.resolve() |
  |                                  |                                 | PromoteDraftToItemService:     |
  |                                  |                                 |   BEGIN TX                     |
  |                                  |                                 |   INSERT item                  |
  |                                  |                                 |   markPromoted(draft, item)    |
  |                                  |                                 |   COMMIT                       |
  |                                  |                                 |                                |
  | --- GET /items ------>           |                                 |                                |
  | <-- new item visible ---         |                                 |                                |
```

## MCP: `auto_register_item` tool

For AI assistants connected through the `POST /mcp` endpoint, the same flow is exposed as a synchronous MCP tool. The image must already live on the server filesystem (uploaded via a sibling endpoint or a shared volume) — the tool takes only the relative path.

### `tools/call` request

```json
{
  "jsonrpc": "2.0",
  "id": "1",
  "method": "tools/call",
  "params": {
    "name": "auto_register_item",
    "arguments": {
      "imagePath": "uploads/item_drafts/20260524_abc123.jpg",
      "barcodeHint": "4901234567890"
    }
  }
}
```

### Response

```json
{
  "jsonrpc": "2.0",
  "id": "1",
  "result": {
    "content": [
      {
        "type": "text",
        "text": "{\"draftId\":42,\"status\":\"confirmed\",\"itemId\":1234,\"errorDetail\":null}"
      }
    ]
  }
}
```

The tool runs the handler synchronously (not via the async bus) so the response carries the final `status` and resulting `itemId`. Possible `status` values:

| `status` | `itemId` | `errorDetail` | Meaning |
|---|---|---|---|
| `confirmed` | int | null | Auto-promotion succeeded. |
| `ready` | null | null | Feature flag was off at processing time. The draft is in the legacy queue awaiting manual confirmation. |
| `failed` | null | string | Unrecoverable error — see `errorDetail`. |

### Required scope

The token making the `tools/call` must carry `items:write`. The tool also indirectly requires the same prerequisites listed above (AI provider configured, at least one category, `ai.auto_register` flag enabled).

## Pipeline internals — what the iterative AI loop does

The AI step uses an Anthropic-style structured-output tool call with this JSON Schema (full call):

```json
{
  "type": "object",
  "properties": {
    "item_name":     { "type": "string" },
    "manufacturer":  { "type": ["string", "null"] },
    "description":   { "type": "string" },
    "jan_code":      { "type": ["string", "null"] },
    "isbn":          { "type": ["string", "null"] },
    "category_hint": { "type": "string" },
    "price":         { "type": ["integer", "null"] }
  },
  "required": ["item_name", "manufacturer", "description", "category_hint"]
}
```

If any of `TARGET_KEYS = [item_name, description, category_hint, jan_code, isbn]` remain empty after the first call, the resolver re-invokes the AI with a **schema subset** containing only the still-missing keys. This reduces token cost and biases the model toward filling exactly the gaps. The retry prompt looks like:

```
この商品画像から、以下の項目のみを再抽出してください: 商品名、説明。
他の項目は出力しないでください。
不明な項目は null にしてください。
```

Loop exit conditions (any one ends the loop):

1. All `TARGET_KEYS` are filled or have an explicit-null verdict.
2. `maxAttempts = 3` reached.
3. AI returns an empty payload (provider misconfigured, rate-limited, content-policy violation, malformed JSON).

`jan_code` / `isbn` are special: if a barcode lookup populated them, they are dropped from the missing list permanently. AI overlays for these keys are discarded — barcode data is trusted over AI guesses.

## Category resolution

`CategoryHintResolver` maps the AI's free-text `category_hint` to a `category_id` using:

1. **Exact match** (case-insensitive, mb-aware) against `category.name_ja` and `name_en`.
2. **Substring match** in either direction (`hint ⊆ name` or `name ⊆ hint`).
3. **Levenshtein** distance ≤ 3 against either name (skipped for very long strings).
4. **Fallback** to the alphabetically-first root category (by `sort_order`, then `id`).

Returns `null` only when the `category` table is entirely empty — in that case promotion fails with a `Failed` status so admins know to seed at least one root.

## Idempotency

The endpoint itself does not currently honour an `Idempotency-Key` header — each multipart upload creates a new `item_draft`. The worker's promotion step is internally idempotent though: `PromoteDraftToItemService` checks `item_draft.promoted_item_id` under `SELECT … FOR UPDATE` before issuing the INSERT, so even if the same `ProcessItemDraft` message is delivered twice the resulting `item` row is created exactly once.

For client-side de-duplication, generate a request-side fingerprint (image hash + barcode hint) and refuse to re-upload if you've already received a `draft_id` for the same fingerprint within the relevant window.

## Worked curl example

```bash
# 1. Issue a pairing code via the admin UI or /mypage/devicePair, then exchange it:
TOKEN=$(curl -s https://saso.sksl.jp/api/v1/mobile/connect \
  -H 'Content-Type: application/json' \
  -d '{"code":"ABCD-EFGH","device_name":"test-cli"}' \
  | jq -r .access_token)

# 2. Enable the flag (admin session required):
curl -X PATCH https://saso.sksl.jp/api/v1/feature-flags/ai.auto_register \
  -H 'Content-Type: application/json' \
  -b "saso_session=..." \
  -d '{"enabled": true}'

# 3. Upload an image for auto-registration:
curl -s -X POST https://saso.sksl.jp/api/v1/items/auto-register \
  -H "Authorization: Bearer $TOKEN" \
  -F "image=@./product.jpg" \
  -F "barcode_hint=4901234567890"
# → {"draft_id":42,"status":"queued","auto_register":true}

# 4. Confirm the resulting item id (after a few seconds):
curl -s "https://saso.sksl.jp/api/v1/items?q=&limit=5" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.data[0]'
```

## Flutter client

The Flutter app exposes `autoRegisterItem(...)` on the same REST client that already implements `createItemDraftWithAi(...)`. See the mobile-app PR companion for the exact Dart signature.

```dart
final response = await restApiClient.autoRegisterItem(
  image: File('/storage/emulated/0/dcim/product.jpg'),
  barcodeHint: '4901234567890',
);
// response: AutoRegisterAcceptedResource { draft_id: 42, status: 'queued', auto_register: true }
```

The mobile UI should poll `GET /items` for the new row or watch its local item-list socket if any.

## Operator checklist before enabling the flag

- [ ] At least one root category exists in the `category` table.
- [ ] At least one AI provider has a valid API key (`/admin/ai-settings`).
- [ ] The `ai.auto_judge` flag is enabled (or auto-synced) — this is the underlying prerequisite for `AiVisionStep` to call the provider.
- [ ] The `uploads/item_drafts/` directory is writable and protected by the existing `.htaccess` (PHP execution disabled).
- [ ] Background worker is running (`php bin/worker process-item-drafts` or the systemd unit).

## See also

- [API Reference](../api.md) — the parent doc, including field semantics for `ItemResource`.
- [API Endpoint Map](../api-endpoint-map.md) — single source of truth for the `/api/v1/*` surface.
- [Error Codes](../error-codes.md) — full SASO error-code catalogue.
- [OpenAPI diff fragment](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi-diff-ai-auto-register.yaml) — drop this into any SDK generator.
- Source files:
  - [`AutoRegisterController`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/src/Presentation/Api/V1/Controller/Item/AutoRegisterController.php)
  - [`AutoRegisterItemTool`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/src/Presentation/Mcp/Tool/AutoRegisterItemTool.php)
  - [`AutoRegisterPipeline`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/src/Application/Enrichment/AutoRegisterPipeline.php)
  - [`IterativeAiResolver`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/src/Application/Enrichment/IterativeAiResolver.php)
  - [`CategoryHintResolver`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/src/Application/Category/CategoryHintResolver.php)
  - [`PromoteDraftToItemService`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/src/Application/ItemDraft/PromoteDraftToItemService.php)
