# API Reference

The REST API lives at `/api/v1/*`. The contract is defined by an OpenAPI 3.1 specification committed under [`config/openapi.yaml`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi.yaml) — see [ADR 0002](architecture/adr/0002-openapi-as-source-of-truth.md) for why it is the source of truth.

## Status

As of M3-D the API is bootstrapped with three meta endpoints. Domain endpoints (items, categories, labels, shelves, auth) land across M3-E and M4.

| Endpoint | Method | What it does |
|---|---|---|
| `/api/v1/health` | `GET` | Liveness probe — returns `{status, version, time}`. Does not check downstream dependencies. |
| `/api/v1/openapi.yaml` | `GET` | Returns this OpenAPI specification verbatim, ready for SDK generators. |
| `/api/v1/docs` | `GET` | Embedded Swagger UI loaded against the spec above. |

## Authentication

Endpoints are unauthenticated until the M3-E auth surface lands. OIDC (browsers) and Bearer JWT (machine clients) follow the [`AuthProvider`](architecture/adr/0003-pluggable-idp.md) contract.

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

- Legacy PHP screens stay **PHPStyle** — server-side rendered, session-cookie auth, CSRF-protected forms. Operators on shared hosting keep their existing UX.
- The REST API enables future SPA clients, a mobile app, and machine-to-machine integrations. The Application layer is shared; only the Presentation transport differs.

The duality is recorded in [ADR 0001](architecture/adr/0001-clean-architecture-ddd.md) (Strangler Fig migration) and [ADR 0002](architecture/adr/0002-openapi-as-source-of-truth.md) (OpenAPI as source of truth).

## Migrating from `request.json`

The legacy `request.json` / `flow.json` URL surface is **deprecated as of M3** and slated for removal in M5. New integrations should target `/api/v1/*`. Existing integrations get a per-feature deprecation window, announced in the [Changelog](changelog.md) when each legacy entry is migrated.
