# API Reference

The REST API lives at `/api/v1/*`. The contract is defined by an OpenAPI 3.1 specification committed under [`config/openapi.yaml`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi.yaml) — see [ADR 0002](architecture/adr/0002-openapi-as-source-of-truth.md) for why it is the source of truth.

## Status

As of M4-D the API is live with meta, feature-flag, and mobile-pairing endpoints. Items, categories, and storage-location endpoints ship throughout M5–M7; labels and shelves remain scheduled.

## Endpoints

### Meta

| Endpoint | Method | What it does |
|---|---|---|
| `/api/v1/health` | `GET` | Liveness probe — returns `{status, version, time}`. Does not check downstream dependencies. |
| `/api/v1/openapi.yaml` | `GET` | Returns this OpenAPI specification verbatim, ready for SDK generators. |
| `/api/v1/docs` | `GET` | Embedded Swagger UI loaded against the spec above. |

### Feature Flags

Operator-managed runtime feature flags backed by the `feature_flag` table (cf. [ADR 0005](architecture/adr/0005-openfeature-with-db-provider.md)).

| Endpoint | Method | What it does |
|---|---|---|
| `/api/v1/feature-flags` | `GET` | List all feature flags ordered by key. |
| `/api/v1/feature-flags` | `POST` | Create a new feature flag (`key` must match `^[a-z0-9][a-z0-9._]{0,119}$`). |
| `/api/v1/feature-flags/{key}` | `GET` | Fetch a single flag by key. |
| `/api/v1/feature-flags/{key}` | `PATCH` | Update `enabled` and/or `value` for a flag. |
| `/api/v1/feature-flags/{key}` | `DELETE` | Delete a feature flag. |

### Mobile — Device Pairing

QR-based device pairing and Flutter client configuration (cf. [ADR 0014](architecture/adr/0014-flutter-pairing-and-mcp-server.md)).

| Endpoint | Method | What it does |
|---|---|---|
| `/api/v1/mobile/pairing-codes` | `POST` | Generate a short-lived (10 min) QR pairing code as a base64 PNG data URI. |
| `/api/v1/mobile/connect` | `POST` | Exchange a pairing token for a device access token and refresh token. |
| `/api/v1/mobile/token/refresh` | `POST` | Refresh an expired device access token. |
| `/api/v1/mobile/config` | `GET` | Return server URL, feature flags, and supported capabilities as a Flutter config bundle. |
| `/api/v1/mobile/tokens` | `GET` | List all active device tokens for the current operator. |
| `/api/v1/mobile/tokens/{id}` | `DELETE` | Revoke a specific device token. |

### Items — Inventory CRUD

Bearer-authenticated item operations. The same `Item` row is shared with the
legacy admin screens (cf. [item registration form](#item-registration-form-legacy))
so any field added here is visible from both transports.

| Endpoint | Method | What it does |
|---|---|---|
| `/api/v1/items` | `GET` | Cursor-paginated list / search. Filters: `q`, `category_id`, `barcode`, `isbn`, `label_code`. |
| `/api/v1/items` | `POST` | Create an item. Optional `Idempotency-Key` header for safe retries. |
| `/api/v1/items/{id}` | `GET` | Single item, including EAV attribute values. |
| `/api/v1/items/{id}` | `PATCH` | Partial update — only keys present in the body are applied. |
| `/api/v1/items/drafts` | `POST` | Multipart upload that enqueues an `item_draft` for AI enrichment. |

#### Fields

`ItemResource` and the request payloads carry the following identifiers
and free-form fields beyond `name` / `categoryId`:

| Field | Type | Notes |
|---|---|---|
| `janCode` | string ≤ 32 | JAN/EAN product barcode. Indexed for `?barcode=` filter. |
| `isbnCode` | string ≤ 32 | ISBN-13 (`978...` / `979...`). Indexed for `?isbn=` filter. |
| `labelCode` | string ≤ 64 | Custom shelf/SKU label. Indexed for `?label_code=` filter. |
| `note` | string ≤ 255 | Free-form remarks. Persisted to `Item.note`. Pass `""` or `null` on PATCH to clear. |

`note` complements the packaging-specific `plaNote` / `paperNote` columns
used by the legacy form: those describe the wrapping; `note` is for
everything else (handling instructions, supplier comments, internal
labelling notes).

#### Item registration form (legacy)

The PHP-rendered registration screen at `/item/add/` shares the same
underlying `Item` row as the REST API. Effective with the May 2026
schema additions:

- `colorName` and `sizeName` are now **optional**. An item can be
  registered without any color/size variants; the `色数×サイズ数 ≤ 100`
  upper bound still applies when either is supplied.
- `note`, `janCode`, and `isbnCode` are exposed as optional inputs on
  both the entry form and the two-step confirmation screen.
- `/item/changeMeta/item/{id}/` is the matching edit panel on the
  `/item/edit/...` page — operators can revise the three identifier /
  remarks fields without going through the REST API.

## Authentication

API endpoints under `/api/v1/feature-flags` and `/api/v1/mobile` require a session cookie (browser) or a Bearer device token (machine clients). The Bearer token is issued by `POST /api/v1/mobile/connect` after a QR pairing. OIDC/SAML-issued tokens follow the same Bearer scheme once those providers ship in M5.

## Errors

Every failure response is `application/problem+json` (RFC 7807) with the SASO `code` and `traceId` extensions. See the [error codes catalogue](error-codes.md). Clients must branch on `code`; `title` and `detail` are localised strings whose wording may change between releases.

## Versioning

URL prefix only (`/api/v1`). Backwards-incompatible changes ship under `/api/v2`. The OpenAPI `info.version` mirrors the contract version.

## Specification

The committed YAML is the canonical artefact. The application also serves it at runtime — useful for SDK generators that prefer to fetch over HTTP.

```bash
curl -s https://your-saso/api/v1/openapi.yaml | head
```

For interactive exploration, point a browser at `/api/v1/docs` for an embedded Swagger UI, or open the [GitHub-hosted YAML](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi.yaml) in your editor's OpenAPI plugin.

## Why two transports

- Legacy PHP screens stay **PHP-rendered** — server-side rendered, session-cookie auth, CSRF-protected forms. Operators on shared hosting keep their existing UX.
- The REST API enables future SPA clients, a mobile app, and machine-to-machine integrations. The Application layer is shared; only the Presentation transport differs.

The duality is recorded in [ADR 0001](architecture/adr/0001-clean-architecture-ddd.md) (Strangler Fig migration) and [ADR 0002](architecture/adr/0002-openapi-as-source-of-truth.md) (OpenAPI as source of truth).

## Migrating from `request.json`

The legacy `request.json` / `flow.json` URL surface is **deprecated as of M3** and slated for removal in M5. New integrations should target `/api/v1/*`. Existing integrations get a per-feature deprecation window, announced in the [Changelog](changelog.md) when each legacy entry is migrated.
