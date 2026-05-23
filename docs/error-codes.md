# Error Codes

SASO surfaces every machine-recoverable failure as a stable identifier of the form `SASO-<DOMAIN>-<NNNN>`. Clients should branch on the `code` field, not on `title` or `detail` — those carry localised wording that may change between releases.

The catalogue is the contract referenced by [ADR 0004](architecture/adr/0004-rfc7807-problem-details.md).

## Format

```
SASO-<DOMAIN>-<NNNN>
```

| Domain | Range | Owner area |
|---|---|---|
| `AUTH`    | `1xxx` | Login, OIDC / SAML provisioning, password change |
| `MOBILE`  | `2xxx` | QR pairing, device tokens, V1 API scope enforcement |
| `LABEL`   | `3xxx` | Label definition and PDF generation |
| `DRAFT`   | `4xxx` | Item-draft uploads (image / metadata validation) |
| `INSTALL` | `5xxx` | Web installer flow |
| `CONFIG`  | `6xxx` | `system_setting` and provider configuration |
| `FLAG`    | `7xxx` | Feature flag evaluation |
| `INFRA`   | `9xxx` | Database / network / unhandled exceptions |
| `MCP`     | `Axxx` | MCP server (JSON-RPC tools, scope enforcement) |
| `PLUGIN`  | `Bxxx` | Plugin registry / loader |

> Note: `4xxx` previously listed "Shelf management" but no shelf-domain codes
> were ever assigned. The range has been reallocated to `DRAFT` (item-draft
> uploads). Shelf-related failures fall under `INFRA` or a future domain to
> be assigned.

Within each domain the four-digit suffix counts upward starting at `0001`. **Codes are append-only.** A code that goes out of use is marked _(deprecated)_ but never reassigned, so logs from older releases stay decodable.

## Catalogue

### `AUTH` — authentication & session

| Code | HTTP | Title | When it is raised |
|---|---|---|---|
| `SASO-AUTH-1001` | 401 | Invalid credentials | Username / password (or OIDC token) did not match an active member |
| `SASO-AUTH-1002` | 401 | Session expired     | Session was valid but exceeded the idle / absolute timeout |
| `SASO-AUTH-1003` | 403 | CSRF token mismatch | Submitted CSRF token did not validate against the session token |
| `SASO-AUTH-1004` | 401 | Authentication required | Endpoint requires an authenticated principal but none was supplied |
| `SASO-AUTH-1005` | 403 | Access denied       | Authenticated, but lacks the role or permission for the requested action |
| `SASO-AUTH-1006` | 503 | Authentication provider is misconfigured | An `AuthProvider` cannot drive a login because its stored configuration is incomplete or unreachable (e.g. discovery URL 404, expired SAML certificate). Operator-actionable; the affected provider stays disabled until the row is fixed |
| `SASO-AUTH-1007` | 400 | Authentication callback could not be matched to a pending request | OIDC `state` / SAML `RelayState` did not match the value the application stored on `beginLogin()`. Most often caused by an expired login attempt (cookies cleared between hops); rarely indicates an attempted CSRF on the callback |
| `SASO-AUTH-1008` | 400 | Authentication callback failed verification | The IdP response (OIDC token signature, SAML assertion signature, nonce, audience, expiry) failed verification |
| `SASO-AUTH-1009` | 423 | Account is locked | Credentials matched but the member account is administratively disabled (PR-A3 REST `/auth/login`) |
| `SASO-AUTH-1010` | 429 | Too many authentication attempts | Per-IP / per-user rate limit on failed attempts exceeded (PR-A3 REST `/auth/login`, `/auth/password`) |
| `SASO-AUTH-1011` | 422 | Authentication request body is malformed | Required JSON fields are missing or of the wrong type (PR-A3 REST `/auth/*`) |
| `SASO-AUTH-1012` | 401 | Current password did not match | `currentPassword` field on `/auth/password` did not match the stored hash (PR-A3) |
| `SASO-AUTH-1013` | 422 | New password does not meet the password policy | `newPassword` violates the length / allowed-characters policy, or equals the current password (PR-A3) |

### `MOBILE` — mobile / QR connect & device tokens

| Code | HTTP | Title | When it is raised |
|---|---|---|---|
| `SASO-MOBILE-2001` | 404 | Pairing code not found        | The QR pairing code does not exist (typo, never issued, or already expired and reaped) |
| `SASO-MOBILE-2002` | 400 | Pairing code has expired      | The pairing code TTL elapsed before the device exchanged it for a token |
| `SASO-MOBILE-2003` | 400 | Pairing code has already been used | The pairing code was already exchanged once — codes are single-use |
| `SASO-MOBILE-2004` | 404 | Device token not found        | The Bearer token references a token row that does not exist |
| `SASO-MOBILE-2005` | 400 | Device token has been revoked | The token row exists but an admin revoked it via the admin console |
| `SASO-MOBILE-2006` | 400 | Device token has expired      | The token row exists but is past its expiry |
| `SASO-MOBILE-2007` | 400 | Invalid mobile connect request | A request to a mobile/V1 endpoint failed input validation (e.g. missing required field) |
| `SASO-MOBILE-2008` | 403 | Scope insufficient for the requested endpoint | The device token's `scp` claim does not include the scope the called endpoint requires. RFC 6749 §3.3 — scopes are normative, not advisory |

### `DRAFT` — item-draft uploads

Emitted by `POST /api/v1/items/drafts` and `POST /api/v1/items/auto-register`
during request validation. Failures that happen later (during background
enrichment / promotion) are surfaced through the `item_draft.status` /
`error_detail` columns rather than as HTTP error codes — see the
[AI Auto-Register integration guide](integrations/ai-auto-register.md)
for the worker-side failure catalogue.

| Code | HTTP | Title | When it is raised |
|---|---|---|---|
| `SASO-DRAFT-4001` | 400 | Image field missing | The multipart payload had no `image` part. |
| `SASO-DRAFT-4002` | 400 | Image upload failed  | The HTTP upload itself errored (`$_FILES['image']['error'] !== UPLOAD_ERR_OK`). |
| `SASO-DRAFT-4003` | 400 | Image too large      | The uploaded file exceeds the 20 MB ceiling. |
| `SASO-DRAFT-4004` | 400 | Unsupported image type | Detected MIME (from the file bytes, not the declared header) is not one of `image/jpeg`, `image/png`, `image/webp`, `image/gif`. |

### `INFRA` — infrastructure

| Code | HTTP | Title | When it is raised |
|---|---|---|---|
| `SASO-INFRA-9000` | 500 | Internal server error | Catch-all for any uncaught exception that does not extend `DomainException`. The full stack is logged; the response body carries only `traceId` |
| `SASO-INFRA-9001` | 503 | Database unavailable  | Could not connect to the configured DSN, or the connection dropped mid-transaction |
| `SASO-INFRA-9002` | 503 | Storage unavailable   | Filesystem path required by the request was not writable / readable |
| `SASO-INFRA-9003` | 404 | Endpoint not found    | API router could not match the request path against any operation declared in `config/openapi.yaml` |
| `SASO-INFRA-9004` | 405 | Method not allowed    | API router matched the path but not the HTTP method; allowed methods are listed in the server log under `context.allowed` |

The remaining domains (`ITEM`, `LABEL`, `INSTALL`, `CONFIG`, `FLAG`) reserve their numeric ranges and will be filled as the corresponding milestones land.

## How clients see them

Every API response from `/api/v1/*` (and every new endpoint added under the [Clean Architecture / DDD layout](architecture/adr/0001-clean-architecture-ddd.md)) is `application/problem+json`:

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/problem+json; charset=utf-8

{
  "type":     "https://docs.willen-federation.org/error-codes#SASO-AUTH-1001",
  "title":    "Invalid credentials",
  "status":   401,
  "detail":   "The submitted password did not match.",
  "instance": "/api/v1/auth/login",
  "code":     "SASO-AUTH-1001",
  "traceId":  "1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12"
}
```

* `code` — branch on this field. It is stable across releases.
* `traceId` — UUIDv4 unique per request. The server log carries the same id under `extra.traceId`; operators paste it into support tickets so engineers can locate the full trace.
* `title` / `detail` — localised strings (English in M3-B; English + Japanese once M3-C ships). Treat as display text only.

Web screens render a friendly message plus the `traceId`. Internals (stack traces, SQL fragments, file paths) never leave the server.

## Adding a new code

A new code touches three places in the same PR:

1. Add a case to `Saso\Domain\Shared\ErrorCode` (`src/Domain/Shared/ErrorCode.php`).
2. Add the row to the table above.
3. Add `error.<code>.title` and `error.<code>.detail` keys to `translations/en.yaml` and `translations/ja.yaml`. Detail strings can include placeholders such as `{traceId}` for runtime interpolation.

Throw a subclass of `DomainException` carrying the new code; the global handler resolves both fields against the request locale and renders the Problem Details payload automatically. Missing Japanese strings fall through to English; missing English strings fall through to the exception message.

## Legacy code paths

Pre-M3 screens still surface ad-hoc strings (`die('invalid csrftoken.')`, `Either::left('error/1/')`). Those migrate to typed exceptions as each feature moves into `src/`. Until then the legacy responses keep their existing shape — the catalogue above governs everything new.

## See also

- [ADR 0004 — RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` codes](architecture/adr/0004-rfc7807-problem-details.md)
- [API Reference](api.md) — request / response shape that surrounds error codes
- [Security](security.md) — disclosure policy and operator hardening
