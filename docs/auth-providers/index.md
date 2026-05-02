# Authentication Providers

SASO supports three kinds of authentication, plugged in behind a single `AuthProvider` interface (cf. [ADR 0003](../architecture/adr/0003-pluggable-idp.md)):

| Provider | Library | When to use it |
|---|---|---|
| `local` | (built-in Argon2id) | Operator-managed accounts. Always available; required for the lockout-safe `bootstrap` administrator. |
| `oidc`  | [`jumbojett/openid-connect-php`](https://github.com/jumbojett/OpenID-Connect-PHP) | Auth0 / Cognito / Microsoft Entra ID / Google Workspace / Keycloak / any OIDC IdP with Discovery + PKCE. |
| `saml`  | [`onelogin/php-saml`](https://github.com/SAML-Toolkits/php-saml) | Enterprise IdPs that ship SAML 2.0 only (older AD FS, shibboleth deployments). |

Multiple instances of each type can be active simultaneously — operators register them in the admin UI (M4) and the login screen renders one button per enabled provider.

## Status (M3-E)

This PR ships the **contract**:

- `Saso\Domain\Auth\AuthProvider` — the interface every provider implements.
- `Saso\Domain\Auth\AuthProviderType` / `AuthProviderId` — discriminator + identity.
- `Saso\Domain\Auth\AuthenticatedIdentity` — the result of a successful login.
- `Saso\Domain\Auth\LoginContext` / `CallbackRequest` / `LogoutContext` / `Redirect` — request and response value objects.
- `Saso\Domain\Auth\ClaimMapping` — operator-configurable mapping from IdP claim names to SASO `Member` fields.
- `Saso\Domain\Auth\Exception\AuthFailedException` / `ProviderMisconfiguredException` — typed exceptions wired to the new `SASO-AUTH-1006/1007/1008` error codes.
- `Saso\Infrastructure\Auth\Crypto\SecretEncryptor` — AES-256-GCM authenticated encryption for OIDC client secrets and SAML private keys at rest.

The Composer dependencies (`jumbojett/openid-connect-php`, `onelogin/php-saml`) are installed and available. Concrete `LocalProvider` / `OidcProvider` / `SamlProvider` implementations land in **M4**, alongside the `auth_provider` DB table and the admin UI.

## What lands in M4

- `auth_provider` and `member_external_identity` tables (cf. ADR 0003).
- `LocalProvider` — bridge to the existing legacy login flow.
- `OidcProvider` — concrete adapter over `jumbojett/openid-connect-php`. Authorization Code + PKCE only; OIDC Discovery (`.well-known/openid-configuration`) accepted.
- `SamlProvider` — concrete adapter over `onelogin/php-saml`. Web Browser SSO profile, ACS POST binding.
- Admin Web UI for adding / editing / disabling providers. Client secrets stored AES-256-GCM-encrypted with `APP_KEY`; the UI shows `●●●` and a "Replace" button — never plaintext.
- Login screen rendering one button per `enabled = 1` provider.
- First-login auto-provisioning that creates a `Member` from the IdP's `email`, `display_name`, and any claims named in `claim_mapping`.

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
