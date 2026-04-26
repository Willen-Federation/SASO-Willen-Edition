# 0004 — RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` error codes

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

The legacy stack returns errors as ad-hoc JSON shapes (`common/FailJsonView`) for the few endpoints that speak JSON, and as HTML pages for everything else. Failure messages are user-facing Japanese strings with no machine-readable identifier; debugging an operator report typically requires correlating the timestamp in the operator's screenshot to a server log, then guessing which code path produced it.

The M3 REST surface (ADR 0002) and the Pluggable IdP work (ADR 0003) introduce many more failure modes that need to be triaged without a screenshot — OIDC discovery failures, claim-mapping mismatches, JWT signature errors, rate limits, validation rejections. We need an error format that:

1. is the same shape across every endpoint;
2. carries a stable, greppable identifier so operators and support staff can map a failure to a runbook entry;
3. is translatable, because the system is bilingual (en + ja) by requirement;
4. ties each response back to a server-side log entry without leaking internals; and
5. survives at least one major refactor without breaking clients.

## Decision Drivers

* **Standards alignment** — clients and proxies should be able to introspect errors without bespoke parsing.
* **Greppable IDs** — `SASO-AUTH-1003` is searchable across logs, code, docs, and translation files.
* **Translatable** — the human-readable `title` and `detail` fields must come from the same i18n catalogue as the rest of the UI.
* **Correlation without leakage** — every response must carry a `traceId` that maps to a full stack trace in the server log, but the response must never carry the stack itself.
* **Backward-incompatible by intention** — the legacy `common/FailJsonView` shape is replaced for `/api/v1/*` and for any new endpoints. Old endpoints retain their shape until they are migrated, at which point they switch in one PR.

## Considered Options

### Option A — Custom envelope (`{ "ok": false, "error": { "code": "...", "message": "..." } }`)

A bespoke shape designed for this codebase.

* (+) Trivial to implement; matches what the existing FailJsonView does today.
* (−) No standards alignment. Every consumer learns a SASO-specific shape.
* (−) No `Content-Type` signalling — clients have to inspect the body to know it is an error.
* (−) HTTP intermediaries (proxies, dashboards) cannot recognise the shape without configuration.

### Option B — RFC 9457 Problem Details with vendor extensions

The successor to RFC 7807. Adopts the same shape but allows additional fields.

* (+) Latest revision of the standard.
* (−) Tooling, validators, and most of the documentation in the wild still target RFC 7807. The two specs are wire-compatible; choosing 9457 buys nothing for clients today.

### Option C — RFC 7807 Problem Details with vendor extensions

A `application/problem+json` body with the standard fields (`type`, `title`, `status`, `detail`, `instance`) plus our extensions (`code`, `traceId`).

* (+) Mature standard, widely supported, recognised by Swagger UI, Postman, k6, and HTTP libraries.
* (+) The vendor-extension mechanism is exactly what we need for `code` and `traceId`.
* (+) Wire-compatible with RFC 9457 if we ever want to upgrade.

## Decision Outcome

**Chosen option: C — RFC 7807 Problem Details with `code` + `traceId` extensions.**

### Wire format

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/problem+json

{
  "type":     "https://docs.willen-federation.org/error-codes#SASO-ITEM-2014",
  "title":    "Item barcode is already in use",
  "status":   422,
  "detail":   "Barcode 4901234567890 is already assigned to item #4711.",
  "instance": "/api/v1/items",
  "code":     "SASO-ITEM-2014",
  "traceId":  "1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12"
}
```

* `type` — URL pointing at the canonical entry in `docs/error-codes.md`.
* `title` — short summary, locale-resolved from `translations/<lang>.yaml`.
* `status` — HTTP status, mirrors the response status line.
* `detail` — request-specific message, locale-resolved.
* `instance` — request URI (no query string, no trailing IDs that would amount to PII unless they are non-sensitive).
* `code` — vendor extension, the stable `SASO-DOMAIN-NNNN` identifier. **This is the field clients should branch on.** `title` and `detail` are for humans.
* `traceId` — UUIDv4 generated per request. The same id appears in the Monolog log entry for the failure. Operators paste this into the support form.

### Code namespace

```
SASO-AUTH-1xxx     authentication and session
SASO-ITEM-2xxx     item domain
SASO-LABEL-3xxx    label / PDF
SASO-SHELF-4xxx    shelf / location
SASO-INSTALL-5xxx  installer and migrations
SASO-CONFIG-6xxx   system_setting and provider configuration
SASO-FLAG-7xxx     feature flag
SASO-INFRA-9xxx    infrastructure (DB, filesystem, third-party APIs)
```

* The four-digit number is allocated sequentially within a domain. Codes are append-only: a code that goes out of use is marked `(deprecated)` in `docs/error-codes.md` but never reassigned.
* `docs/error-codes.md` is the canonical catalogue. Adding a code requires editing this file, the translation YAMLs, and the throwing site in the same PR.

### Server side

* `src/Domain/Shared/ErrorCode/ErrorCode.php` — `enum` of every defined code, with `domain()`, `httpStatus()`, and `translationKey()` methods.
* `src/Domain/Shared/DomainException.php` — base exception carrying an `ErrorCode` and a context array.
* `src/Presentation/Http/Problem/ProblemDetails.php` — value object that materialises the wire shape.
* `src/Presentation/Http/Problem/Renderer.php` — turns a `ProblemDetails` into a `Response` with `Content-Type: application/problem+json`.
* `src/Presentation/Http/Problem/ExceptionHandler.php` — global handler. Catches `DomainException` and renders Problem Details; catches `Throwable` and renders `SASO-INFRA-9000` with a fresh `traceId`, while logging the full stack at `error` level.
* `src/Infrastructure/Logging/MonologFactory.php` — Monolog with rotating-file handler, `traceId` injected via processor.

### Translations

* Translation keys are `error.<code>.title` and `error.<code>.detail`. They live in `translations/en.yaml` and `translations/ja.yaml`.
* Missing translations fall back to English; missing English entries fall back to the code itself with a CI warning, not a 500.

### Coexistence with legacy

* `/api/v1/*` and any new endpoint use this format from day one.
* Existing endpoints (the Web UI behind `framework/Router.php`) continue to use HTML responses and `common/FailJsonView` until each feature migrates. When a feature is migrated to the new layout, its error responses switch in the same PR and the legacy code path is removed.

## Consequences

* Every API failure carries a stable `code`, a translatable `title`/`detail`, and a `traceId` that anchors it to a server log. Support workflows can be scripted around these fields.
* `docs/error-codes.md` becomes a contract: every code documented there must have a translation and at least one production code path that throws it. CI enforces this in M3-B.
* Clients can rely on `application/problem+json` for content-negotiation. They can branch on `code`, ignore `title`/`detail` (which may change wording), and present the localised string the server already produced.
* Adding a new error costs three edits (catalogue, two translations) plus a thrown site. We accept this overhead in exchange for greppable, translatable failures.
* Internal exceptions never leak. Anything not derived from `DomainException` becomes `SASO-INFRA-9000` with the trace stored server-side. Operators get a reference; attackers get a UUID.
* This ADR is wire-compatible with RFC 9457; if we adopt the newer revision later, we change the spec citation, not the response shape.
