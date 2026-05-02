# Authentication Providers

SASO supports three kinds of authentication, plugged in behind a single `AuthProvider` interface (cf. [ADR 0003](../architecture/adr/0003-pluggable-idp.md)):

| Provider | Library | When to use it |
|---|---|---|
| `local` | (built-in Argon2id) | Operator-managed accounts. Always available; required for the lockout-safe `bootstrap` administrator. |
| `oidc`  | [`jumbojett/openid-connect-php`](https://github.com/jumbojett/OpenID-Connect-PHP) | Auth0 / Cognito / Microsoft Entra ID / Google Workspace / Keycloak / any OIDC IdP with Discovery + PKCE. |
| `saml`  | [`onelogin/php-saml`](https://github.com/SAML-Toolkits/php-saml) | Enterprise IdPs that ship SAML 2.0 only (older AD FS, shibboleth deployments). |

Multiple instances of each type can be active simultaneously — operators register them in the admin UI (M4) and the login screen renders one button per enabled provider.

## What is shipped

### Domain contract (M3-E)

- `Saso\Domain\Auth\AuthProvider` — the interface every provider implements.
- `Saso\Domain\Auth\AuthProviderType` / `AuthProviderId` — discriminator + identity.
- `Saso\Domain\Auth\AuthenticatedIdentity` — the result of a successful login.
- `Saso\Domain\Auth\LoginContext` / `CallbackRequest` / `LogoutContext` / `Redirect` — request and response value objects.
- `Saso\Domain\Auth\ClaimMapping` — operator-configurable mapping from IdP claim names to SASO `Member` fields.
- `Saso\Domain\Auth\Exception\AuthFailedException` / `ProviderMisconfiguredException` — typed exceptions wired to the new `SASO-AUTH-1006/1007/1008` error codes.
- `Saso\Infrastructure\Auth\Crypto\SecretEncryptor` — AES-256-GCM authenticated encryption for OIDC client secrets and SAML private keys at rest.

### Admin UI & REST API (M4+)

- `auth_provider` and `member_external_identity` tables (migration `20260426120002`).
- `LocalProvider` / `OidcProvider` / `SamlProvider` — concrete adapters over `jumbojett/openid-connect-php` and `onelogin/php-saml`.
- **Admin Web UI** at `/auth/provider/new` and `/auth/provider/{id}/edit`:
  - Flavor cards (Generic OIDC · Auth0 · AWS Cognito · Firebase) reveal only the fields relevant to the chosen provider.
  - Auth0: `domain` + optional `audience`; Discovery URL auto-built as `https://{domain}/.well-known/openid-configuration`.
  - Cognito: `region` + `user_pool_id` + optional `hosted_ui_domain`; Discovery URL shown live.
  - Firebase: `firebase_project_id` + optional `hd` (Workspace domain restriction); uses Google's discovery document.
  - **Connection test button** — calls `POST /api/v1/auth/providers/{id}/test` and shows parsed OIDC endpoints or an error badge inline.
  - Client secrets stored AES-256-GCM-encrypted with `APP_KEY`; the UI shows `●●●` — never plaintext.
- **REST API** — see [`/docs/api.md`](../api.md#auth-providers) for the full reference.
- Login screen renders one button per `enabled = 1` provider.
- First-login auto-provisioning creates a `Member` from the IdP's `email`, `display_name`, and any claims in `claim_mapping`.

## Lockout safety

Every release that touches the auth layer keeps two escape hatches in place (cf. ADR 0003):

1. The installer creates a **`bootstrap` local administrator** that cannot be demoted while it is the last `bootstrap` member, and always uses `LocalProvider`.
2. Setting **`SAFE_MODE=true`** in `.env` disables every non-local provider regardless of DB state. A misconfigured OIDC tenant cannot brick the instance.

## Per-IdP setup guides (planned)

The M4 admin UI ships per-IdP step-by-step guides under this section. Each will cover:

- What to register at the IdP (Redirect URI / Entity ID / ACS URL).
- What to paste into the SASO admin form.
- How to test the provider with the built-in health probe.

Planned coverage: **Keycloak (dev-bundled)**, **Auth0**, **AWS Cognito**, **Microsoft Entra ID**, **Google Workspace**, generic **SAML 2.0**.

## See also

- [ADR 0003 — Pluggable IdP](../architecture/adr/0003-pluggable-idp.md)
- [Error Codes](../error-codes.md) — `SASO-AUTH-1xxx` namespace
- [Security](../security.md) — disclosure policy and operator hardening
