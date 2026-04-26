# 0014 — Flutter device pairing + MCP server endpoint

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

M6 expands SASO's surface beyond the browser:

1. **Flutter mobile app** — operators on the warehouse floor scan barcodes and update stock from their phones. The app must pair with a SASO instance via QR code or URL, persist a long-lived token, and use it to call `/api/v1/*`.
2. **MCP server** — SASO exposes a Model Context Protocol endpoint so external AI agents (Claude Desktop, Cursor, custom assistants) can query inventory directly. Tools surfaced: `search_items`, `get_item`, `list_storage_locations`, `register_item` (write, gated by per-token scopes).

Both surfaces share a need: **issue scoped, revocable tokens to non-browser clients**.

## Decision Drivers

- **Reuse the Pluggable IdP** (ADR 0003) — humans authenticate the same way regardless of client. Mobile operators sign in via OIDC/SAML/local; the device-pairing flow trades that session for a device token.
- **Operators control trust** — they see, name, and revoke devices and MCP clients from the admin UI.
- **Standards alignment** — pairing follows OAuth 2.0 Device Authorization Grant (RFC 8628). MCP follows the Anthropic-published Model Context Protocol (1.0).
- **Compliance with ADR 0001** — endpoints live under `src/Presentation/Api/V1/` (paired devices) and `src/Presentation/Mcp/` (MCP server); domain talks to a `DeviceTokenRepository` interface.

## Considered Options for pairing

### Option A — Long-lived API tokens, manually pasted into the app

* (+) Simplest.
* (−) Operators copy-paste a 64-char token from their phone. Bad UX.

### Option B — RFC 8628 OAuth 2.0 Device Authorization Grant

The phone calls `/api/v1/auth/devices/initiate`, receives a `device_code` + `user_code` + verification URL. The verification URL is encoded as a QR code rendered on a PC the operator is already signed in on. The operator scans it, confirms the pairing, and the app polls `/api/v1/auth/devices/token` until it receives the `device_token`. Standards-aligned, well-supported, and the QR/URL split matches the user's request verbatim.

### Option C — One-time pairing code typed into the app

A hybrid of A and B: operator signs in on PC → admin UI generates a 6-digit code → operator types it into the phone within 5 minutes.

* (+) No QR scanner needed.
* (−) Worse UX than scanning. RFC 8628 is the mature pattern; reinventing a smaller protocol gives nothing.

## Considered Options for MCP

### Option A — Don't ship MCP

* (−) The user explicitly asked for it.

### Option B — Ship MCP as a separate process

* (−) Doubles deployment surface. The MCP server needs to read the same DB, auth, and feature flags as the app — so it might as well be the same codebase.

### Option C — MCP endpoint inside the existing PHP app

The MCP server runs as a route under `src/Presentation/Mcp/`. It speaks JSON-RPC 2.0 over HTTP (with optional SSE for long-running tools). Tools are PHP classes implementing `McpTool`, registered via `system_setting` (`mcp.tools.<name>.enabled = bool`).

## Decision Outcome

**Pairing: Option B (RFC 8628).**
**MCP: Option C (in-app endpoint).**

### Pairing schema

```sql
CREATE TABLE device_token (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    member_id       BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(120) NOT NULL,                 -- operator-named ("Akira's iPhone")
    token_hash      CHAR(64) NOT NULL,                     -- sha256(token); plaintext shown once
    scopes          JSON NOT NULL,                         -- ['items:read','items:write','locations:read']
    pairing_code    CHAR(8) NULL,                          -- short-lived device code; nulled on activation
    activated_at    DATETIME NULL,
    last_used_at    DATETIME NULL,
    expires_at      DATETIME NULL,                         -- NULL = no expiry
    created_at      DATETIME NOT NULL,
    UNIQUE KEY uniq_token (token_hash),
    KEY idx_member (member_id, activated_at)
);
```

### Pairing flow

1. **Mobile** → `POST /api/v1/auth/devices/initiate` with a name (`"Akira's iPhone"`). Server returns `{ pairing_code, verification_uri, verification_uri_complete, expires_in: 300, interval: 5 }`. The token is **not** issued yet.
2. **Mobile renders** `verification_uri_complete` as a QR (via the bundled `chillerlan/php-qrcode` lib).
3. **Operator** opens the QR on their PC (already signed in) → lands on `/admin/devices/pair?code=XXXXXXXX` → confirms.
4. **Mobile polls** `POST /api/v1/auth/devices/token` with the device code. On success it receives `{ device_token, expires_at }`.
5. The token is sent as `Authorization: Bearer <token>` on every subsequent API call.

Token verification is constant-time `hash_equals(stored_hash, sha256(presented))`. Plaintext is never stored.

### MCP endpoint

`POST /mcp` accepts JSON-RPC 2.0. Methods:

- `initialize` / `tools/list` — discovery.
- `tools/call` — dispatch to a registered `McpTool`.

Tools live in `src/Presentation/Mcp/Tool/`:

```
SearchItemsTool       → SearchIndex (ADR 0010) keyword + similarity
GetItemTool           → ItemRepository
ListStorageLocationsTool → StorageLocationRepository (ADR 0011)
RegisterItemTool      → ItemRegistrationUsecase (gated by scope items:write)
```

Authentication: same Bearer token as the mobile app. Scopes are checked per tool. A token without `items:write` cannot call `RegisterItemTool`.

A `Saso\Presentation\Mcp\Tool\McpTool` interface keeps tools pluggable. Adding a tool is a class + a registration entry.

### Admin UI

`/admin/devices` lists every issued token with name, scopes, last-used timestamp, and a "Revoke" button. Revocation deletes the row; subsequent presentations of the token resolve to "no row" → 401.

`/admin/mcp` lists registered MCP tools with their enabled-state toggle. The MCP endpoint refuses to advertise or dispatch a disabled tool.

### Error codes

```
SASO-AUTH-1009  Pairing code expired or unknown
SASO-AUTH-1010  Device token revoked
SASO-AUTH-1011  Scope insufficient for the requested operation
SASO-MCP-A001   Unknown MCP tool
SASO-MCP-A002   MCP request malformed (JSON-RPC envelope invalid)
```

(MCP gets a fresh top-level domain `MCP` in the catalogue; range `Axxx`.)

### Lockout safety

`SAFE_MODE=true` (cf. ADR 0003) disables both surfaces. Pairing requests get 503; MCP returns a JSON-RPC error.

## Consequences

- Mobile clients pair via standard OAuth 2.0 Device Grant, no copy-paste.
- The MCP endpoint is part of the main PHP app — no second deployment surface.
- Tokens are revocable from the admin UI; operators see exactly which devices and AI agents are connected.
- Adding a third client surface later (a CLI, a desktop app) reuses the same `device_token` plumbing — no new auth shape needed.
- This ADR does **not** specify the Flutter app codebase. The pairing endpoints are defined by `config/openapi.yaml` (cf. ADR 0002); the mobile codebase consumes that spec via SDK generation.
