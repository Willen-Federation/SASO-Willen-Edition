# API Reference

The REST API at `/api/v1/*` and the OpenAPI 3.1 specification land in **M3 (REST + i18n + Errors)**. This page is a placeholder so the navigation is stable while M3 is in flight.

## Planned shape

| Aspect | Plan |
|---|---|
| Routing | `nikic/fast-route` for the `/api/v1/*` tree, side-by-side with the legacy PHP screens |
| Source of truth | A hand-written `config/openapi.yaml` (OpenAPI 3.1) — code is generated/checked from it, not the other way around |
| Client documentation | Swagger UI embedded into this site via `mkdocs-render-swagger-plugin` |
| Authentication | OIDC via `jumbojett/openid-connect-php` for browsers + Bearer JWT (RS256, 15 min) for non-browser clients |
| Errors | RFC 7807 Problem Details with a `code` field carrying the `SASO-DOMAIN-NNNN` ID — see [Error Codes](error-codes.md) |
| Versioning | URL prefix only (`/api/v1`); breaking changes ship under `/api/v2` |
| Pagination / filtering | Cursor-based pagination with `limit` + `cursor` query parameters |

## Why two transports

- The legacy PHP screens stay **PHPStyle** — server-side rendered, session cookie auth, CSRF-protected forms. Operators on shared hosting keep their existing UX.
- The REST API enables future SPA clients, a mobile app, and machine-to-machine integrations. The Application layer is shared; only the Presentation transport differs.

This duality is recorded in the planned **ADR 0001** (M3) and **ADR 0002** (OpenAPI as source of truth).

## Until M3 ships

Programmatic integrations should use the existing `request.json` / `flow.json` URL surface — but be aware those are deprecated as of M3 and removed in M5. New integrations should wait or be ready to migrate.
