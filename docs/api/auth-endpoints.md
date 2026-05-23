# REST authentication endpoints

The `/api/v1/auth/*` surface replaces the legacy `POST /auth/start/` form
flow for clients that cannot use a browser session cookie (mobile, CLI,
non-interactive integrations). Three operations are exposed:

| Operation | Method | Path | Authentication |
|---|---|---|---|
| Login | `POST` | `/api/v1/auth/login` | None (public) |
| Logout | `POST` | `/api/v1/auth/logout` | `Authorization: Bearer <access_token>` |
| Password change | `POST` | `/api/v1/auth/password` | `Authorization: Bearer <access_token>` |

All three endpoints return RFC 7807 `application/problem+json` errors with
SASO-specific `code` and `traceId` extensions. See
[Error codes](../error-codes.md) for the full catalogue.

The successful response shape and access-token semantics are identical to
`POST /api/v1/mobile/connect` — the new endpoints simply add a typed-credential
issuance path so the same client code paths can consume both QR pairing
and username/password flows.

The legacy `POST /auth/start/` form flow stays alive while clients
migrate. v3.0 of the Flutter app (and the eventual server-side cleanup)
will remove it. See the Flutter repo's
[PR-B2 legacy login deprecation note](https://github.com/Willen-Federation/SASO-Willen-Edition-Flutter/pulls?q=PR-B2)
for the client-side rollout.

## `POST /api/v1/auth/login`

Verifies a username + password pair and issues an OAuth2-style token pair.

### Request

```http
POST /api/v1/auth/login HTTP/1.1
Content-Type: application/json

{
  "username": "alice",
  "password": "hunter2hunter2",
  "deviceName": "Chrome on macOS"
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `username` | string | yes | The member's login id (same value used by the legacy `/auth/start/` form). |
| `password` | string | yes | Plain-text password. Send over HTTPS only. |
| `deviceName` | string | no | Human-readable label recorded on the new `device_token` row. Defaults to `"Unknown device"`. |

### Success response

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "access_token":  "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type":    "Bearer",
  "expires_in":    3600,
  "refresh_token": "Y0zM3nOpQ4rT5uV6wX7y",
  "device_id":     42,
  "device_name":   "Chrome on macOS",
  "expires_at":    "2026-05-23T13:00:00+00:00"
}
```

The `access_token` is the same 1-hour HS256 JWT used by every other
authenticated endpoint. The `refresh_token` is a 1-year opaque token —
store it securely (e.g. flutter_secure_storage). Rotate via
`POST /api/v1/mobile/token/refresh`.

### Errors

| HTTP | Code | Cause |
|---|---|---|
| 401 | `SASO-AUTH-1001` | Username / password mismatch (intentionally vague — does not distinguish "no such user" from "wrong password"). |
| 422 | `SASO-AUTH-1011` | Request body is missing required fields or a field is of the wrong type. |
| 423 | `SASO-AUTH-1009` | Account is administratively locked. |
| 429 | `SASO-AUTH-1010` | Too many failed attempts; retry after the limiter window clears (default: 10 attempts per (IP, username) tuple in 5 minutes). |

#### Example: wrong password

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/problem+json

{
  "type":     "https://docs.willen-federation.org/error-codes#SASO-AUTH-1001",
  "title":    "Invalid credentials",
  "status":   401,
  "detail":   "Invalid username or password.",
  "instance": "/api/v1/auth/login",
  "code":     "SASO-AUTH-1001",
  "traceId":  "1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12"
}
```

### `curl` example

```sh
curl -i -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"hunter2hunter2","deviceName":"curl test"}' \
  https://saso.example.com/api/v1/auth/login
```

## `POST /api/v1/auth/logout`

Revokes the current session's refresh token. Equivalent to
`DELETE /api/v1/mobile/tokens/{id}` except the device id is read from
the Bearer JWT's `sub` claim instead of being supplied in the URL.

Idempotent — calling twice returns `204` both times.

### Request

```http
POST /api/v1/auth/logout HTTP/1.1
Authorization: Bearer <access_token>
```

No request body.

### Success response

```http
HTTP/1.1 204 No Content
```

### Errors

| HTTP | Code | Cause |
|---|---|---|
| 401 | `SASO-AUTH-1004` | `Authorization` header missing, malformed, or carrying an unverifiable / expired JWT. |

### `curl` example

```sh
curl -i -X POST \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  https://saso.example.com/api/v1/auth/logout
```

## `POST /api/v1/auth/password`

Changes the authenticated member's password. The server:

1. Verifies the supplied `currentPassword` against the stored hash.
2. Enforces the password policy on `newPassword` (8–64 characters,
   `[A-Za-z0-9_-]` only, must differ from `currentPassword`).
3. Writes a fresh Argon2id digest.
4. Revokes every OTHER device's refresh token for the same member so
   any session minted before the change has to re-authenticate. The
   current device's tokens stay valid.

### Request

```http
POST /api/v1/auth/password HTTP/1.1
Authorization: Bearer <access_token>
Content-Type: application/json

{
  "currentPassword": "hunter2hunter2",
  "newPassword":     "brandnewpw2025"
}
```

### Success response

```http
HTTP/1.1 204 No Content
```

### Errors

| HTTP | Code | Cause |
|---|---|---|
| 401 | `SASO-AUTH-1004` | Bearer missing / invalid / expired. |
| 401 | `SASO-AUTH-1012` | `currentPassword` did not match the stored hash. |
| 422 | `SASO-AUTH-1011` | Request body malformed / missing fields. |
| 422 | `SASO-AUTH-1013` | `newPassword` violates the password policy (length, allowed characters, or equals `currentPassword`). |
| 429 | `SASO-AUTH-1010` | Too many failed `currentPassword` attempts. |

### `curl` example

```sh
curl -i -X POST \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"currentPassword":"hunter2hunter2","newPassword":"brandnewpw2025"}' \
  https://saso.example.com/api/v1/auth/password
```

## Error code reference

The PR-A3 endpoints introduced the following new codes; the legacy
codes referenced above are documented in
[Error codes](../error-codes.md):

| Code | HTTP | Title | When raised |
|---|---|---|---|
| `SASO-AUTH-1009` | 423 | Account is locked | The credentials matched but the account is administratively disabled. |
| `SASO-AUTH-1010` | 429 | Too many authentication attempts | The configured per-IP / per-user rate limit on failed attempts has been exceeded. |
| `SASO-AUTH-1011` | 422 | Authentication request body is malformed | Required JSON fields are missing or of the wrong type. |
| `SASO-AUTH-1012` | 401 | Current password did not match | The `currentPassword` field on `/auth/password` did not match the stored hash. |
| `SASO-AUTH-1013` | 422 | New password does not meet the password policy | The new password is shorter than 8 chars, longer than 64, contains characters outside `[A-Za-z0-9_-]`, or equals the current password. |

Two existing codes are reused by the new endpoints (and listed only for
convenience):

| Code | HTTP | Title | When raised |
|---|---|---|---|
| `SASO-AUTH-1001` | 401 | Invalid credentials | Username / password mismatch on `/auth/login`. Body wording is intentionally the same for "no such user" and "wrong password" to prevent enumeration. |
| `SASO-AUTH-1004` | 401 | Authentication required | `Authorization` header missing, malformed, or carrying an unverifiable JWT (`/auth/logout`, `/auth/password`). |

## Migration roadmap

* **PR-A3 (this PR)** — server-side endpoints land.
* **PR-B3 (Flutter side)** — the app switches from posting to
  `/auth/start/` to calling `/auth/login` for username + password
  login. Logout migrates from clearing the cookie to calling
  `/auth/logout`.
* **v3.0** — the legacy `/auth/start/` POST flow is removed. Clients
  that still issue form posts after this release see a hard 410.
