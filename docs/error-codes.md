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
| `ITEM`    | `2xxx` | Items and item operations |
| `LABEL`   | `3xxx` | Label definition and PDF generation |
| `SHELF`   | `4xxx` | Shelf management |
| `INSTALL` | `5xxx` | Web installer flow |
| `CONFIG`  | `6xxx` | `system_setting` and provider configuration |
| `FLAG`    | `7xxx` | Feature flag evaluation |
| `INFRA`   | `9xxx` | Database / network / unhandled exceptions |

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

### `INFRA` — infrastructure

| Code | HTTP | Title | When it is raised |
|---|---|---|---|
| `SASO-INFRA-9000` | 500 | Internal server error | Catch-all for any uncaught exception that does not extend `DomainException`. The full stack is logged; the response body carries only `traceId` |
| `SASO-INFRA-9001` | 503 | Database unavailable  | Could not connect to the configured DSN, or the connection dropped mid-transaction |
| `SASO-INFRA-9002` | 503 | Storage unavailable   | Filesystem path required by the request was not writable / readable |

The remaining domains (`ITEM`, `LABEL`, `SHELF`, `INSTALL`, `CONFIG`, `FLAG`) reserve their numeric ranges and will be filled as M3-D and M4 land.

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
