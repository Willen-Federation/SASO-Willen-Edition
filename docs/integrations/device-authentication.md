# Device Authentication Integration Guide

SASO supports three client environments — web browser, Flutter mobile app, and
Electron desktop app. All three converge on the same API token pair
(`access_token` / `refresh_token`) after authentication, but the **acquisition
path** differs by client because of how each runtime can hold state and open a
browser window.

---

## Prerequisites

Every client must know the server base URL before any auth operation. The server
URL is the root of the SASO instance — for example `https://saso.example.com`.

**Discovery endpoint** — after the user enters the server URL the client
SHOULD call:

```http
GET /api/v1/auth/providers
```

No credentials required. The response (`AuthProviderDiscovery`) describes
which providers are enabled and recommends an `authStrategy` so the client
can skip a provider-chooser screen when only one provider is configured.

```json
{
  "serverName":    "My Library",
  "version":       "2.3.0",
  "mobileSetupUrl": "https://saso.example.com/m/setup",
  "authStrategy":  "local-only",
  "providers": [
    { "id": 1, "type": "local",  "name": "Local account",  "enabled": true },
    { "id": 2, "type": "oidc",   "name": "Google Workspace", "enabled": false }
  ]
}
```

`authStrategy` values:

| Value | Meaning |
|---|---|
| `local-only` | Only a `local` provider is active — skip the chooser. |
| `default-only` | Only one non-local provider — redirect directly. |
| `user-choice` | Multiple active providers — show a chooser. |

---

## Authentication tokens

All non-browser clients authenticate subsequent API calls with a Bearer JWT.

| Token | Algorithm | Lifetime | Use |
|---|---|---|---|
| `access_token` | HS256 JWT | 1 hour | `Authorization: Bearer <access_token>` on every API request. |
| `refresh_token` | Opaque string | 1 year | Renew an expired `access_token` via `POST /api/v1/mobile/token/refresh`. Rotated on each use — always persist the new value. |

Both tokens are issued by whichever endpoint completes authentication:
`POST /api/v1/mobile/connect` (QR pairing), `POST /api/v1/auth/login`
(username + password), or — for Electron — the same `connect` endpoint
after the loopback callback delivers the pairing token.

Store tokens in the platform's secure storage
(`flutter_secure_storage` for Flutter; Electron's `userData` directory with
file-system permissions restricted to the OS user).

---

## Web browser

The web browser authenticates with a **server-side session cookie** — not
a Bearer JWT. No client-side token storage is required.

### Flow

```
Browser                                  Server
  |                                         |
  |  GET /auth/start/{providerId}           |
  |---------------------------------------->|
  |                                         |  Sets PHPSESSID cookie
  |  POST /auth/start/{providerId}          |  (or OIDC/SAML redirect)
  |  id=alice&password=…                    |
  |---------------------------------------->|
  |                                         |  Verifies credentials
  |  302  Location: /item/list/             |  Sets $_SESSION['id']
  |<----------------------------------------|
  |                                         |
  |  GET /item/list/   (Cookie: PHPSESSID)  |
  |---------------------------------------->|
```

On failure the server redirects to `{restoredPath}/error/1/` (or
`auth/start/error/1/` when no `restoredPath` was supplied) so the login
form re-renders with the error banner.

Third-party IdP (OIDC / SAML) adds an intermediate redirect to the
external identity provider but the final result is the same: a session
cookie is written on callback return.

### No token pair issued

Browser sessions are scoped to `PHPSESSID` only. The `/api/v1/*` endpoints
that require `Authorization: Bearer` are not intended for browser-based
access. Admin console screens under `/admin/` authenticate via the same
session cookie.

---

## Flutter mobile app

Flutter obtains a permanent JWT token pair using one of two paths:

| Path | When to use |
|---|---|
| QR pairing | The admin opens `/admin/mobile/devices/` in a browser, clicks "Generate QR", and the user scans it with the Flutter app camera. |
| Username + password (API login) | Direct credential entry inside the app — no QR scan required. |

### Path A — QR pairing

```
Admin browser                  Server                  Flutter app
      |                           |                          |
      |  POST /mobile/pairing-codes (admin session)          |
      |-------------------------->|                          |
      |  201 { qrDataUri, token } |                          |
      |<--------------------------|                          |
      |                           |                          |
      |  Display QR code          |                          |
      |                           |  Scan QR                 |
      |                           |  Decode: SASO1:<token>|<url>
      |                           |<-------------------------|
      |                           |  POST /mobile/connect    |
      |                           |  { token, deviceName }   |
      |                           |<-------------------------|
      |                           |  201 TokenPairResponse   |
      |                           |------------------------->|
      |                           |                          |  Persist tokens
      |                           |                          |  flutter_secure_storage
```

**QR payload format:**

```
SASO1:<base64url_token>|<server_base_url>
```

The `SASO1:` prefix is recognised exclusively by the SASO Flutter app. A
generic QR reader shows opaque text — no OS deep-link interception.

**POST `/api/v1/mobile/connect` request:**

```http
POST /api/v1/mobile/connect HTTP/1.1
Content-Type: application/json

{
  "token":      "<base64url_token extracted from QR>",
  "deviceName": "Pixel 8 Pro"
}
```

**Response (201):**

```json
{
  "access_token":  "eyJhbGci…",
  "token_type":    "Bearer",
  "expires_in":    3600,
  "refresh_token": "Y0zM3nOpQ4rT5uV6wX7y",
  "device_id":     42,
  "device_name":   "Pixel 8 Pro",
  "expires_at":    "2026-05-25T15:00:00+00:00"
}
```

Pairing tokens are one-time use, expire in 10 minutes, and are stored
SHA-256-hashed in the database. A replayed token returns `404
SASO-MOBILE-2004`.

### Path B — Username + password (API login)

```
Flutter app                    Server
     |                            |
     |  GET /api/v1/auth/providers|
     |--------------------------->|
     |  200 { providers, ... }    |
     |<---------------------------|
     |                            |
     |  POST /api/v1/auth/login   |
     |  { username, password,     |
     |    deviceName }            |
     |--------------------------->|
     |  201 TokenPairResponse     |
     |<---------------------------|
     |                            |  Persist tokens
```

**POST `/api/v1/auth/login` request:**

```http
POST /api/v1/auth/login HTTP/1.1
Content-Type: application/json

{
  "username":   "alice",
  "password":   "hunter2hunter2",
  "deviceName": "Pixel 8 Pro"
}
```

The response shape is identical to `POST /api/v1/mobile/connect`.

### OIDC / SAML on Flutter (third-party login)

When the server exposes a non-local provider, the Flutter app opens the
`/m/setup` URL in an in-app WebView or the system browser. The server
renders the web login form (same HTML as the browser flow) and on success
writes the pairing token to `$_SESSION['auth.return_to']`. The post-login
redirect delivers the pairing token to `POST /api/v1/mobile/connect` via
the existing web callback path.

Use `GET /api/v1/auth/providers` first: if `authStrategy` is `local-only`
you can skip the WebView and go straight to username + password.

### Token renewal

```http
POST /api/v1/mobile/token/refresh HTTP/1.1
Content-Type: application/json

{ "refresh_token": "<current_refresh_token>" }
```

Response is a full `TokenPairResponse`. The old `refresh_token` is
invalidated immediately — the app MUST persist the new token. Replaying
the old token returns `404 SASO-MOBILE-2004`.

---

## Electron desktop app

Electron cannot hold a PHP session cookie across process restarts, so it
uses the same JWT token pair as Flutter. Because it is a desktop app with
a system browser, authentication is delegated to the **web** entirely — the
Electron process never handles credentials directly.

### Before first login — server URL entry

The user enters the server base URL in the onboarding screen. The app
persists this URL and calls `GET /api/v1/auth/providers` to validate
connectivity and discover available providers.

### Login flow (loopback redirect)

```
Electron main process                Server (PHP)             System browser
        |                                |                          |
        |  Start loopback HTTP server    |                          |
        |  listening on 127.0.0.1:NNNN  |                          |
        |                                |                          |
        |  Open browser to /m/setup      |                          |
        |  (or /auth/start/{id})         |                          |
        |-------------------------------------------------------------->|
        |                                |                          |
        |                                |  GET /m/setup            |
        |                                |<-------------------------|
        |                                |  Picks a provider,       |
        |                                |  stores /m/issue-pairing |
        |                                |  in $_SESSION[           |
        |                                |  'auth.return_to']       |
        |                                |  303 → /auth/start/{id}  |
        |                                |------------------------->|
        |                                |                          |
        |                                |  User fills login form   |
        |                                |  POST /auth/start/{id}   |
        |                                |<-------------------------|
        |                                |  Credentials verified    |
        |                                |  Session key consumed    |
        |                                |  302 → /m/issue-pairing  |
        |                                |------------------------->|
        |                                |                          |
        |                                |  GET /m/issue-pairing    |
        |                                |<-------------------------|
        |                                |  POST /mobile/pairing-   |
        |                                |  codes (server-side)     |
        |                                |  PairingCode issued,     |
        |                                |  returns HTML with       |
        |                                |  SASO1:<token> fragment  |
        |                                |------------------------->|
        |                                |                          |
        |  Fragment arrives in loopback  |                          |
        |  server via JS POST to         |                          |
        |  http://127.0.0.1:NNNN/submit-token
        |<--------------------------------------------------------------|
        |                                |                          |
        |  POST /api/v1/mobile/connect   |                          |
        |  { token, deviceName }         |                          |
        |------------------------------->|                          |
        |  201 TokenPairResponse         |                          |
        |<-------------------------------|                          |
        |                                |                          |
        |  Store tokens in auth.json     |                          |
        |  (userData directory)          |                          |
        |  Close loopback server         |                          |
        |  Close browser window          |                          |
```

**Key security properties of the loopback approach:**

- The Electron process never sees the user's password — credentials are
  entered in the server-rendered web form inside the system browser.
- The `SASO1:` token is delivered via a fragment (`#SASO1:…`) which the
  browser never sends to the loopback server in a redirect; a small JS
  snippet on the `/m/issue-pairing` page reads `location.hash` and POSTs
  only the token to `http://127.0.0.1:NNNN/submit-token`.
- The loopback server validates a one-time CSRF state nonce before
  accepting the token.
- `redirect_uri` values accepted by the server are restricted to
  `http://127.0.0.1:*` (wildcard port) for the loopback case and
  `jp.willen.saso://callback` for the Flutter deep-link case — nothing
  else passes the `RedirectUriAllowlist` check.

### OIDC / SAML on Electron

Third-party login (OIDC, SAML) works transparently: the system browser
follows the external IdP redirect normally. The server's
`LoginOrchestrator::handleCallback()` restores `$_SESSION['auth.return_to']`
(`/m/issue-pairing`) so the pairing-code issuance step runs on callback
return, and the token is delivered to the loopback server exactly as in
the local-login flow above. No Electron-side changes are needed to support
new IdP types.

### IPC surface (renderer → main)

The Electron main process exposes auth operations through a secure IPC
bridge (`contextBridge`). The renderer process never accesses the file
system or Node directly.

| Channel | Direction | Description |
|---|---|---|
| `auth:start` | renderer → main | Opens the system browser to begin the web login flow. |
| `auth:result` | main → renderer | Delivers `{ access_token, refresh_token, device_id }` after a successful loopback callback. |
| `auth:logout` | renderer → main | Calls `POST /api/v1/auth/logout`, deletes `auth.json`. |
| `auth:status` | renderer → main | Returns `{ loggedIn, serverUrl }` from the persisted `auth.json`. |

### Token storage

Tokens are written to `auth.json` in Electron's `app.getPath('userData')`
directory. This path is OS-specific:

| OS | Path |
|---|---|
| macOS | `~/Library/Application Support/SASO/auth.json` |
| Windows | `%APPDATA%\SASO\auth.json` |
| Linux | `~/.config/SASO/auth.json` |

The file is protected by OS-level user permissions (mode `600` on POSIX).
It is never transmitted outside the machine.

---

## Token refresh (all non-browser clients)

All clients using Bearer tokens share the same refresh endpoint:

```http
POST /api/v1/mobile/token/refresh HTTP/1.1
Content-Type: application/json

{ "refresh_token": "<current_refresh_token>" }
```

**200 response** — identical shape to the initial token pair. The old
`refresh_token` is invalidated. The client MUST store the new
`refresh_token` before discarding the old one.

**Error responses:**

| HTTP | Code | Cause |
|---|---|---|
| 400 | `SASO-MOBILE-2002` | Request body malformed or `refresh_token` field missing. |
| 404 | `SASO-MOBILE-2004` | Token not found, already consumed, expired, or revoked. Re-authenticate from scratch. |

Clients should treat `404` as a sign-out signal and restart the auth flow.

---

## Token revocation

Tokens can be revoked by the device owner (self-logout) or by an
administrator (remote wipe):

| Operation | Endpoint | Auth |
|---|---|---|
| Self-logout | `POST /api/v1/auth/logout` | `Authorization: Bearer <access_token>` |
| Admin revoke | `DELETE /api/v1/mobile/tokens/{id}` | Admin session cookie |
| List all devices | `GET /api/v1/mobile/tokens` | Admin session cookie |

After revocation the device receives `SASO-MOBILE-2005` on its next
authenticated API call. The client should intercept this code, clear the
stored tokens, and restart the auth flow.

---

## Error codes reference

| Code | HTTP | Raised by | Cause |
|---|---|---|---|
| `SASO-AUTH-1001` | 401 | `POST /auth/login` | Invalid username or password. |
| `SASO-AUTH-1004` | 401 | Any authenticated endpoint | Bearer missing, malformed, or expired. |
| `SASO-AUTH-1009` | 423 | `POST /auth/login` | Account is administratively locked. |
| `SASO-AUTH-1010` | 429 | `POST /auth/login` | Too many failed attempts (10 per 5 min per IP/username). |
| `SASO-AUTH-1011` | 422 | `POST /auth/login` | Request body malformed or required field missing. |
| `SASO-MOBILE-2002` | 400 | `POST /mobile/connect`, `/mobile/token/refresh` | Malformed request body. |
| `SASO-MOBILE-2004` | 404 | `POST /mobile/connect`, `/mobile/token/refresh` | Pairing token or refresh token not found / already consumed / expired. |
| `SASO-MOBILE-2005` | 401 | Any `deviceJwt`-secured endpoint | Token revoked by admin or self-logout. |

Full catalogue: [Error codes](../error-codes.md).

---

## Comparison by client type

| Aspect | Web browser | Flutter | Electron |
|---|---|---|---|
| Credential entry | Server-rendered HTML form | App-native form (local) or in-app WebView (OIDC/SAML) | System browser (all providers) |
| Auth transport | Session cookie (`PHPSESSID`) | JWT Bearer token | JWT Bearer token |
| Token storage | HTTP-only cookie (server-managed) | `flutter_secure_storage` | `auth.json` in `userData` |
| Third-party IdP | Full browser redirect | WebView → same pairing flow | System browser → loopback → same pairing flow |
| Token renewal | Automatic (session) | `POST /mobile/token/refresh` | `POST /mobile/token/refresh` |
| Logout | `GET /auth/logout` (form) | `POST /api/v1/auth/logout` + clear storage | `POST /api/v1/auth/logout` + delete `auth.json` |

---

## See also

- [`GET /api/v1/auth/providers`](../api/auth-endpoints.md) — provider discovery
- [`POST /api/v1/auth/login`](../api/auth-endpoints.md) — username + password login
- [`POST /api/v1/mobile/connect`](../api.md#mobile-device-pairing) — pairing code exchange
- [`POST /api/v1/mobile/token/refresh`](../api.md#mobile-device-pairing) — token renewal
- [Auth Providers](../auth-providers/index.md) — provider configuration guide
- [OpenAPI specification](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/config/openapi.yaml) — machine-readable contract
