# API Reference

The REST API lives at `/api/v1/*`. The contract is defined by an OpenAPI 3.1 specification committed under [`config/openapi.yaml`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi.yaml) — see [ADR 0002](architecture/adr/0002-openapi-as-source-of-truth.md) for why it is the source of truth.

## Status

| Endpoint | Method | Operation ID | What it does |
|---|---|---|---|
| `/api/v1/health` | `GET` | `getHealth` | Liveness probe — returns `{status, version, time}`. Does not check downstream dependencies. |
| `/api/v1/openapi.yaml` | `GET` | `getOpenApiSpec` | Returns this OpenAPI specification verbatim, ready for SDK generators. |
| `/api/v1/docs` | `GET` | `getSwaggerUi` | Embedded Swagger UI loaded against the spec above. |
| `/api/v1/auth/providers` | `GET` | `listAuthProviders` | List all registered auth providers (secrets never returned). |
| `/api/v1/auth/providers/{id}` | `GET` | `getAuthProvider` | Fetch a single provider by ID. Returns 404 if not found. |
| `/api/v1/auth/providers/{id}/test` | `POST` | `testAuthProvider` | Probe the provider's discovery URL; returns parsed endpoints or 422/502 on failure. |
| `/api/v1/feature-flags` | `GET` | `listFeatureFlags` | List all feature flags. |
| `/api/v1/feature-flags` | `POST` | `createFeatureFlag` | Create a new feature flag. |
| `/api/v1/feature-flags/{key}` | `GET` | `getFeatureFlag` | Get a single feature flag. |
| `/api/v1/feature-flags/{key}` | `PUT` | `updateFeatureFlag` | Update a feature flag. |
| `/api/v1/feature-flags/{key}` | `DELETE` | `deleteFeatureFlag` | Delete a feature flag. |

## Auth Providers

The `/api/v1/auth/providers` surface lets management tooling and future SPAs read the IdP registry without touching the legacy admin PHP pages.

### `GET /api/v1/auth/providers`

Returns all registered providers. Client secrets are **never** returned; `hasSecret: true/false` indicates whether one is stored.

**Response** `200 application/json`:

```json
{
  "total": 2,
  "data": [
    {
      "id": 1,
      "name": "Auth0 Staff",
      "type": "oidc",
      "issuerOrMetadataUrl": "https://acme.us.auth0.com/.well-known/openid-configuration",
      "clientId": "abc123",
      "hasSecret": true,
      "scopes": "openid email profile",
      "claimMapping": { "_config": { "flavor": "auth0", "domain": "acme.us.auth0.com" } },
      "enabled": true,
      "isDefault": false,
      "createdAt": "2026-04-26T12:00:00+00:00",
      "updatedAt": "2026-04-26T12:00:00+00:00"
    }
  ]
}
```

### `GET /api/v1/auth/providers/{id}`

Fetch a single provider by integer ID.

**Path params**: `id` — provider ID (must be ≥ 1; 422 on invalid, 404 if not found).

**Response** `200` — same shape as one item from the list above.

### `POST /api/v1/auth/providers/{id}/test`

Probes the provider's discovery/metadata URL and returns the parsed endpoint set. No credentials are sent to the IdP — only a GET to the well-known document.

**Response** `200 application/json`:

```json
{
  "ok": true,
  "issuer": "https://acme.us.auth0.com/",
  "authorizationEndpoint": "https://acme.us.auth0.com/authorize",
  "tokenEndpoint": "https://acme.us.auth0.com/oauth/token",
  "userinfoEndpoint": "https://acme.us.auth0.com/userinfo",
  "jwksUri": "https://acme.us.auth0.com/.well-known/jwks.json"
}
```

**Error responses**:

| Status | When |
|---|---|
| `422` | Provider has no discovery URL configured. |
| `502` | Discovery URL unreachable or did not return valid JSON. |

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
