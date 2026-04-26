# Error Codes

The `SASO-DOMAIN-NNNN` error code catalogue ships in **M3 (REST + i18n + Errors)**. This page reserves the slot.

## Format

```
SASO-<DOMAIN>-<NNNN>
```

| Domain | Range | Owner area |
|---|---|---|
| `AUTH` | `1xxx` | Login, OIDC / SAML provisioning, password change |
| `ITEM` | `2xxx` | Items and item operations |
| `LABEL` | `3xxx` | Label definition and PDF generation |
| `SHELF` | `4xxx` | Shelf management |
| `INSTALL` | `5xxx` | Web installer flow |
| `INFRA` | `9xxx` | Database / network / unhandled exceptions |

Within each domain the four-digit suffix counts upward starting at `0001`. Codes are immutable once published — a deprecated code is replaced by a new code rather than rewritten.

## How clients see them

API responses use [RFC 7807 Problem Details](https://datatracker.ietf.org/doc/html/rfc7807) with `Content-Type: application/problem+json`:

```json
{
  "type": "https://docs.example/errors/SASO-AUTH-1003",
  "title": "Invalid credentials",
  "status": 401,
  "detail": "The supplied id or password did not match an active member.",
  "code": "SASO-AUTH-1003",
  "traceId": "1f3d4f12-0f5a-4d7e-9a24-2b69b6c3a4ef",
  "instance": "/api/v1/auth/login"
}
```

Web screens render a friendly message plus the `traceId` so support requests can be correlated with server logs.

## Until M3 ships

The legacy PHP screens currently use ad-hoc strings (`die('invalid csrftoken.')`, `Either::left('error/1/')`). Those will be migrated to typed exceptions and the catalogue above when the M3 PRs roll in.

## See also

- [API Reference](api.md) — request / response shape that surrounds error codes.
- [Security](security.md) — `traceId` is a UUIDv4; full stack traces never leave the server in production.
