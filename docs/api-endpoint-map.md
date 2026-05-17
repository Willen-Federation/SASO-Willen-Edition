# API Endpoint Map (authoritative)

This page is the single source of truth that clients (especially the Flutter
mobile app) consult when wiring against the SASO `/api/v1/*` surface. The
underlying contracts live in [`config/openapi.yaml`](../config/openapi.yaml) — if
you change one, change the other; CI rejects drift.

## Conventions

- **Base URL:** `https://<host>/api/v1` for the REST surface. `POST /mcp` for
  the JSON-RPC 2.0 MCP endpoint. `https://<host>/mypage/*` for the web
  self-service routes.
- **Authentication options:**
  - `Bearer` — short-lived HS256 JWT issued by `POST /api/v1/mobile/connect`
    and rotated by `POST /api/v1/mobile/token/refresh`. Mobile clients use this
    for everything except the public/discovery endpoints.
  - `Admin session` — PHP session cookie issued by the web login. Reserved for
    operator UIs and routes that touch *any* device's tokens.
  - `Web session + CSRF` — same cookie + the per-session CSRF token; used by
    the MyPage self-service routes.
- **Scope claim (`scp`):** OAuth2-style scopes enforced by
  `JwtGuard::requireScope()`. A token missing the required scope gets
  `HTTP 403 SASO-MOBILE-2008`. The default-paired device token carries
  `items:read`, `items:write`, `feature_flags:read`.
- **Errors:** `application/problem+json` per RFC 7807. The `code` field
  follows `SASO-{DOMAIN}-{NNNN}`; see [`docs/error-codes.md`](error-codes.md)
  for the full catalogue.
- **Idempotency:** create/update endpoints accept `Idempotency-Key: <uuid>`
  (24 h TTL) so retries are safe.

## Endpoints

### Meta & discovery (public)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `GET` | `/api/v1/health` | none | — | Liveness probe |
| `GET` | `/api/v1/openapi.yaml` | none | — | Live spec dump |
| `GET` | `/api/v1/docs` | none | — | Swagger UI |
| `GET` | `/api/v1/auth/providers` | none | — | Auth provider discovery |

### Feature flags (operator)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `GET` | `/api/v1/feature-flags` | none | — | List flags |
| `POST` | `/api/v1/feature-flags` | none | — | Create flag |
| `GET` | `/api/v1/feature-flags/{key}` | none | — | Get flag |
| `PATCH` | `/api/v1/feature-flags/{key}` | none | — | Update flag |
| `DELETE` | `/api/v1/feature-flags/{key}` | none | — | Delete flag |

> These are deliberately session-less today so the operator UI can wire them
> without re-authenticating; production deployments place them behind a
> reverse-proxy ACL.

### Mobile pairing & tokens

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `POST` | `/api/v1/mobile/pairing-codes` | **Admin session** | — | PC-side: mint a pairing code + QR. **Not callable from the phone** — use `/mypage/devicePair` for self-service. |
| `POST` | `/api/v1/mobile/connect` | pairing token (in body) | — | Phone-side: exchange pairing code for JWT pair |
| `POST` | `/api/v1/mobile/token/refresh` | refresh token (in body) | — | Rotate access + refresh tokens |
| `GET` | `/api/v1/mobile/config` | Bearer | `feature_flags:read` | Pull the offline config bundle (flags, capabilities) |
| `GET` | `/api/v1/mobile/tokens` | **Admin session** | — | Operator audit of issued device tokens. **Not for mobile clients.** |
| `DELETE` | `/api/v1/mobile/tokens/{id}` | **Admin session** | — | Operator revocation. **Not for mobile clients.** |

### Items (Bearer)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `GET` | `/api/v1/items` | Bearer | `items:read` | Search / list (cursor pagination: `cursor`, `limit`, `q`, `category_id`) |
| `POST` | `/api/v1/items` | Bearer | `items:write` | Create (supports `Idempotency-Key`) |
| `GET` | `/api/v1/items/{id}` | Bearer | `items:read` | Get one item with EAV attributes |
| `PATCH` | `/api/v1/items/{id}` | Bearer | `items:write` | Partial update (supports `Idempotency-Key`) |
| `POST` | `/api/v1/items/drafts` | Bearer | `items:write` | Multipart upload → enqueue an `item_draft` row. See [DraftCreateController](../src/Presentation/Api/V1/Controller/Item/DraftCreateController.php). |

### Categories & storage locations (Bearer)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `GET` | `/api/v1/categories` | Bearer | `items:read` | Flat or tree list |
| `GET` | `/api/v1/storage-locations` | Bearer | `items:read` | Root or child locations |
| `GET` | `/api/v1/storage-locations/{id}` | Bearer | `items:read` | One location |
| `GET` | `/api/v1/storage-locations/{id}/items` | Bearer | `items:read` | Items shelved at a location |

### Barcode (Bearer)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `GET` | `/api/v1/barcode/{code}` | Bearer | `items:read` | JAN/EAN/ISBN lookup. Returns the linked item id if any, else just barcode metadata. |

### MCP (Bearer)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `POST` | `/mcp` | Bearer | per-tool | JSON-RPC 2.0 dispatch (`tools/list`, `tools/call`, resources, prompts). The "analyze image" path lives here, not as a REST endpoint. |

### Web self-service (browser)

| Method | Path | Auth | Scope | Purpose |
|--------|------|------|-------|---------|
| `POST` | `/mypage/devicePair` | Web session + CSRF | — | Self-service pairing-code generation |
| `POST` | `/mypage/deviceRevoke` | Web session + CSRF | — | Self-service revoke |

## Flutter client mapping

The Flutter app talks to the same endpoints via
[`lib/data/datasources/remote/v1/rest_api_client.dart`](https://github.com/saso/SASO-Willen-Edition-Flutter/blob/main/lib/data/datasources/remote/v1/rest_api_client.dart).
Method ↔ endpoint mapping the client is expected to follow:

| Dart method | HTTP call |
|-------------|-----------|
| `fetchItem(id)` | `GET /items/{id}` |
| `searchItems(q, categoryId)` | `GET /items?q=&category_id=&limit=20` |
| `fetchAllItemsRaw(cursor, limit)` | `GET /items?cursor=&limit=` |
| `createItem(body, idempotencyKey)` | `POST /items` |
| `updateItem(id, patch, idempotencyKey)` | `PATCH /items/{id}` |
| `createItemDraftWithAi(...)` | `POST /items/drafts` (multipart) |
| `fetchCategories()` | `GET /categories` |
| `fetchShelf(id)` | `GET /storage-locations/{id}` |
| `fetchItemsByShelf(id)` | `GET /storage-locations/{id}/items` |
| `lookupBarcode(code)` | `GET /barcode/{code}` |
| `connectWithPairingToken(...)` | `POST /mobile/connect` |
| `refreshAccessToken(refresh)` | `POST /mobile/token/refresh` |
| `fetchConfigBundle()` | `GET /mobile/config` |
| `checkHealth()` | `GET /health` |
| `McpClient.analyzeItemImage(...)` | `POST /mcp` (`tools/call`) |

The following Dart methods are **deliberately absent** because their backend
endpoints require an admin session and cannot be reached from a phone:

- `createPairingCode()` — initiate via PC operator screen or `/mypage/devicePair`.
- `fetchDeviceTokens()` / `revokeDeviceToken()` — manage paired devices via
  `/mypage` in a browser.

The mobile settings screen should expose a "Manage paired devices on web"
button that opens `<server>/mypage/` via `url_launcher`.

## Error-code highlights mobile clients should handle

| Code | HTTP | Meaning | Suggested UX |
|------|------|---------|--------------|
| `SASO-MOBILE-2001` | 401 | Missing/invalid `Authorization` header | Force re-pair |
| `SASO-MOBILE-2002` | 401 | Expired/invalid Bearer | Try refresh, then re-pair |
| `SASO-MOBILE-2003` | 401 | Refresh token revoked | Force re-pair |
| `SASO-MOBILE-2004` | 401 | Refresh token expired | Force re-pair |
| `SASO-MOBILE-2008` | 403 | Token lacks the required scope | Show "device permissions insufficient" banner, link to re-pair |
| `SASO-DRAFT-4001..4099` | 400 | Draft upload validation | Surface field-level error |
| `SASO-INFRA-9000` | 5xx | Unhandled server error | Retry-with-backoff, show `traceId` |

When emitting 401/403 the backend includes
`WWW-Authenticate: Bearer realm="api", error="…", scope="…"` so RFC 6750 –
compatible HTTP libraries can parse the failure mode without reading the body.
