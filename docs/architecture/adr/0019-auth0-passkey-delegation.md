# ADR-0019: Delegate passkey storage and WebAuthn ceremonies to Auth0

* Status: Accepted
* Date: 2026-05-23
* Tracks: [Issue #228](https://github.com/Willen-Federation/SASO-Willen-Edition/issues/228), supersedes the local WebAuthn plan from [Issue #76](https://github.com/Willen-Federation/SASO-Willen-Edition/issues/76)
* Builds on: [ADR-0003 pluggable-idp](0003-pluggable-idp.md)

## Context

Passkey (WebAuthn) support in My Page has been disabled since [Issue #203](https://github.com/Willen-Federation/SASO-Willen-Edition/issues/203) — the legacy implementation stored `attestationObject` without verifying the signature, allowing pre-auth account takeover. The original re-implementation plan in [Issue #228](https://github.com/Willen-Federation/SASO-Willen-Edition/issues/228) was to wire `web-auth/webauthn-lib` (^5.3, already in `composer.json`) into local `webauthn_credential` / `webauthn_challenge` tables.

Two problems with the local-only plan:

1. **High blast radius if mis-implemented.** WebAuthn signature verification is subtle (RP-ID hash check, sign-count rollback detection, attestation format dispatch, COSE→PEM key conversion). The previous regression that motivated Issue #203 was caused by exactly this surface area, and SASO has no security-engineering staff to re-audit it on every dependency bump.
2. **Auth0 already runs the ceremony for us.** SASO supports Auth0 as a first-class IdP via `src/Infrastructure/Auth/Provider/Auth0Provider.php`. Auth0 implements WebAuthn registration and assertion server-side, validated against the FIDO Alliance's compliance suite, and exposes the resulting credentials through the Management API.

## Decision

Delegate the entire WebAuthn ceremony — registration, assertion, attestation verification, sign-count tracking — to Auth0. SASO stores zero passkey material.

Concretely:

* **Registration**: `/mypage/passkeyBegin/` redirects the user to Auth0's `/authorize` with `prompt=login`. The Auth0 tenant is configured to prompt for passkey enrollment after a fresh login (either via the built-in "WebAuthn Roaming / Platform" authentication methods or via a post-login Action). The callback path is the standard `/auth/callback`; an `auth.purpose=passkey_enroll` session marker tells the callback handler to skip the JIT-provisioning branch and redirect back to My Page with a status banner.
* **Listing & deletion**: `MyPageUsecase` and `/mypage/passkeyDelete/` call Auth0's Management API (`GET|DELETE /api/v2/users/{user_id}/authentication-methods`) via a server-side M2M client. The user's Auth0 `sub` is resolved from the local `member_external_identity` table for the Auth0-typed provider.
* **Login via passkey**: Handled implicitly by the existing OIDC redirect — when the user hits Auth0's login screen with a passkey already enrolled, Auth0 offers it as a sign-in option. SASO does not need a dedicated passkey-login endpoint, so the `/auth/passkeyBegin/` and `/auth/passkeyComplete/` 410 stubs in `index.php` remain (they exist only to block the pre-fix LegacyJS that used to call them).
* **Local WebAuthn tables** (`webauthn_credential`, `webauthn_challenge`): Stop reading and writing them; mark deprecated in code comments. A follow-up migration will drop them once we are confident no other consumer (admin reports, dashboards) references them. We do not drop in this PR to keep the change reversible.

## Consequences

### Positive

* **Zero SASO-side cryptography.** No risk of a regression on the scale of Issue #203. The library `web-auth/webauthn-lib` stays in `composer.json` for now (it is still used by future device-attestation work, ADR-0014) but does not get re-wired into the login or My Page flow.
* **Built-in MFA path.** Once a passkey is enrolled, Auth0's Universal Login automatically offers it for subsequent SASO logins. No SASO-side feature flag needed.
* **Familiar UX.** Auth0 Universal Login provides browser-native `navigator.credentials.create()` and `get()` ceremonies with proper RP-ID enforcement and resident-key support.

### Negative

* **Auth0 tenant config is now a hard dependency for passkey support.** Self-hosted deployments without Auth0 see a disabled card on My Page ("Passkey support requires Auth0; configure an Auth0 provider to enable"). This is acceptable — passkeys are an enhancement, not a requirement.
* **Management API M2M credentials must be provisioned.** New environment variables (see "Configuration" below). Without them, the passkey list and delete operations fail with a banner. Registration via redirect still works because it does not need the Management API.
* **Auth0 outage = passkey list outage.** If the Management API is unreachable, My Page renders an empty list with an error banner. The user can still sign in (Auth0 login is on a different code path) and can still call `/mypage/passkeyDelete/` (it returns 502 to the user-facing redirect).

### Neutral

* **No changes to the AuthProvider abstraction.** Passkey enrollment is a SASO-specific flow that talks directly to Auth0 (not via the IdP-neutral `LoginOrchestrator`). The Auth0 SDK is invoked inline in `PasskeyBeginDIContainer`; this is duplication of ~15 lines of SdkConfiguration setup but avoids polluting the IdP-neutral `LoginContext` with `prompt=login` and similar Auth0-specific hints.

## Configuration

New environment variables (`docker-compose.yml`, `.env.example`):

| Var | Purpose |
| --- | --- |
| `AUTH0_M2M_DOMAIN` | Auth0 tenant domain for the Management API. Defaults to the Auth0 provider's `_config.domain` if unset. |
| `AUTH0_M2M_CLIENT_ID` | Machine-to-Machine application client id (separate Auth0 app, not the SPA/Regular Web app used for login). |
| `AUTH0_M2M_CLIENT_SECRET` | Machine-to-Machine application client secret. |

Required Auth0 admin steps:

1. **Authentication > Authentication Methods**: Enable "WebAuthn with FIDO Device Biometrics" (or "Passkeys" if the tenant has the Passkeys add-on).
2. **Applications > Machine-to-Machine**: Create an app authorized to call `https://{tenant}/api/v2/` with scopes `read:authentication_methods`, `delete:authentication_methods`.
3. **Branding > Universal Login**: Confirm New Universal Login is enabled (Classic Universal Login does not support the passkey enrollment screen).

## Alternatives considered

### 1. Local `web-auth/webauthn-lib` implementation (Issue #228 original plan)

Rejected: re-introduces the cryptographic surface area that caused Issue #203. Acceptable only if SASO retains a security reviewer; not the case today.

### 2. Auth0 My Account API (`/me/v1/authentication-methods`)

Considered. This is the newer self-service endpoint and avoids the M2M secret. Rejected for this PR because:

* It requires modifying the login flow to also request an `audience=https://{tenant}/me/` token with `read:me:authentication_methods`, `delete:me:authentication_methods` scopes.
* It requires storing and refreshing the user's access token across requests (currently only the id_token is persisted).

Worth revisiting once those prerequisites are in place. The Management API approach in this ADR can be swapped for the My Account API without changing the My Page UI or the `Auth0PasskeyService` interface — only its internal HTTP target changes.

### 3. Redirect to Auth0's hosted account portal

Auth0 does not ship a hosted user-facing portal for authenticator management as of 2026-05. Rejected.

## References

* Auth0 Management API: `GET /api/v2/users/{id}/authentication-methods`, `DELETE /api/v2/users/{id}/authentication-methods/{authentication_method_id}`
* Auth0 PHP SDK: `auth0/auth0-php ^8.19` (in `composer.json`)
* Prior art in this repo: `mypage/UnlinkProviderDIContainer.php` (CSRF + DB cleanup pattern)
