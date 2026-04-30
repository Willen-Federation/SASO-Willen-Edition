# Configuring authentication providers

SASO's pluggable IdP layer (ADR 0003) accepts six provider flavors:

| Flavor              | Class                       | Discovery URL                                                                                  |
|---------------------|-----------------------------|------------------------------------------------------------------------------------------------|
| `local`             | `LocalProvider`             | n/a — uses the legacy `Member` table (Argon2id with SHA-256 upgrade)                           |
| `auth0`             | `Auth0Provider`             | `https://{tenant}.auth0.com/.well-known/openid-configuration`                                  |
| `cognito`           | `CognitoProvider`           | `https://cognito-idp.{region}.amazonaws.com/{user_pool_id}/.well-known/openid-configuration`   |
| `firebase`          | `FirebaseProvider`          | `https://accounts.google.com/.well-known/openid-configuration` (Google OAuth, **not** Firebase) |
| `oidc` (default)    | `GenericOidcProvider`       | any IdP that publishes a discovery document (Keycloak, Okta, Microsoft Entra, …)              |
| `saml`              | `SamlProvider`              | IdP entity ID + metadata URL or inline XML                                                     |

Provider rows live in `auth_provider` (M4 migration `20260426120002_create_auth_provider`). The `claim_mapping.JSON` column carries both:

* the SASO-field → IdP-claim map (top-level keys), and
* a reserved `_config` envelope with provider-specific extras.

## Per-flavor `_config` cheatsheet

### Auth0

```json
{
  "subject": "sub", "email": "email", "display_name": "name",
  "_config": {
    "flavor": "auth0",
    "domain": "acme.eu.auth0.com",
    "audience": "https://saso.api",
    "redirect_uri_allowlist": ["https://saso.example.jp/auth/callback/2"]
  }
}
```

### AWS Cognito

```json
{
  "subject": "sub", "email": "email", "display_name": "name",
  "_config": {
    "flavor": "cognito",
    "region": "ap-northeast-1",
    "user_pool_id": "ap-northeast-1_AbCd1",
    "hosted_ui_domain": "acme.auth.ap-northeast-1.amazoncognito.com"
  }
}
```

Cognito does not advertise `end_session_endpoint`; logout uses `/logout?client_id=…&logout_uri=…` on the Hosted UI domain.

### Firebase / Google

```json
{
  "subject": "sub", "email": "email", "display_name": "name",
  "_config": {
    "flavor": "firebase",
    "project_id": "saso-prod",
    "hd": "saso.example.jp"
  }
}
```

`hd` (optional) pins the Workspace domain — non-matching accounts are rejected.

> Note: server-side OIDC sign-in for Firebase uses **Google's** discovery document and **Google Cloud Console OAuth Client IDs**, not the Firebase project config. The Firebase client SDK (`securetoken.google.com/{project_id}`) is separate and out of scope here.

### Generic OIDC

```json
{
  "subject": "sub", "email": "email", "display_name": "name",
  "_config": { "flavor": "oidc" }
}
```

### Generic SAML

`type='saml'`, `issuer_or_metadata_url` = IdP entity ID or metadata URL.

```json
{
  "subject": "NameID",
  "email": "urn:oid:0.9.2342.19200300.100.1.3",
  "display_name": "urn:oid:2.16.840.1.113730.3.1.241",
  "_config": {
    "flavor": "saml",
    "entity_id": "https://saso.example.jp/auth/saml/sp/3",
    "nameid_format": "urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress",
    "idp_x509_cert": "...",
    "sp_x509_cert": "...",
    "sp_private_key": "..."
  }
}
```

## Routing

The orchestrator handles four short-circuit paths in `index.php` (added in M4-D2):

* `GET  /auth/start/{providerId}`     — issues the IdP redirect
* `GET  /auth/callback/{providerId}`  — completes the round trip, writes `$_SESSION['id']`, redirects to `return`
* `POST /auth/saml/acs/{providerId}`  — SAML AssertionConsumerService
* `GET  /auth/saml/sls/{providerId}`  — SAML SingleLogoutService

`APP_KEY` (32 random bytes, base64-encoded) must be set in `.env` before any encrypted secret can be read. When `APP_KEY` is missing, the auth endpoints redirect to `./auth/start?error=auth_unavailable` and the legacy username/password form keeps working.

## Member roles

The new admin screens (`/auth/providers`, `/admin/feature-flags`, `/verify/start`) gate access via `Member.role` (added by migration `M4/20260428000000_add_role_to_member`). The bootstrap admin row is automatically promoted to `role='admin'`; every other row defaults to `'operator'`.

## JIT provisioning

When an IdP login lands on a member that does not yet exist locally, `LoginOrchestrator::handleCallback()`:

1. tries `member_external_identity` for `(provider_id, sub)`,
2. falls back to a local lookup by email,
3. otherwise creates a fresh `Member` row with a random Argon2id password (the user can never sign in via the local flow with this stub password — only via the IdP).

## Admin UI flow

Providers are created at `/auth/provider/new` and edited at `/auth/provider/{id}/edit`. The form uses Alpine.js flavor cards so only the fields relevant to the chosen provider type are shown:

| Flavor card | Required fields | Auto-built Discovery URL |
|-------------|-----------------|--------------------------|
| Generic OIDC | `issuer_or_metadata_url`, `client_id`, `client_secret` | — (paste the URL directly) |
| Auth0 | `auth0_domain`, `client_id`, `client_secret` | `https://{domain}/.well-known/openid-configuration` |
| AWS Cognito | `cognito_region`, `cognito_user_pool_id`, `client_id`, `client_secret` | `https://cognito-idp.{region}.amazonaws.com/{user_pool_id}/.well-known/openid-configuration` |
| Firebase | `firebase_project_id`, `client_id`, `client_secret` | `https://accounts.google.com/.well-known/openid-configuration` |
| SAML | `issuer_or_metadata_url`, `entity_id`, `idp_x509_cert` | — |

After saving, use the **Test connection** button to probe the discovery URL without leaving the page. The test calls `POST /api/v1/auth/providers/{id}/test` and displays the resolved OIDC endpoints on success.

## REST API

| Method | Path | What it does |
|--------|------|--------------|
| `GET` | `/api/v1/auth/providers` | List all providers; secrets omitted, `hasSecret` bool returned |
| `GET` | `/api/v1/auth/providers/{id}` | Fetch a single provider |
| `POST` | `/api/v1/auth/providers/{id}/test` | Probe discovery URL, return parsed endpoint set |

Full request / response shapes are in [`docs/api.md`](../api.md#auth-providers).

## See also

* ADR 0003 — pluggable IdP contract
* ADR 0017 — TailAdmin migration
* [API Reference — Auth Providers](../api.md#auth-providers)
