# 0003 — Pluggable IdP via `AuthProvider` interface (OIDC + SAML + Local)

* Status: accepted
* Date: 2026-04-26
* Deciders: Willen Federation contributors

## Context and Problem Statement

The application's M0 authentication is a local username + password store on the `member` table. Since M1 the passwords are Argon2id, but the model is still single-source: an operator who wants to delegate authentication to Auth0, Cognito, Microsoft Entra ID, Google Workspace, Keycloak, or a SAML-only IdP has nowhere to plug in.

Three constraints make this non-trivial:

1. Operators choose the IdP at deploy time, not at compile time. The fork is shipped as a single artefact and used by multiple organisations with different identity stacks.
2. Operators must be able to configure new providers from the **Web UI** without editing files or restarting Apache (per the project's "Web-first configuration" requirement).
3. We must never lock the operator out of their own instance. A misconfigured OIDC client or a revoked SAML certificate must not bring authentication down with no recovery path.

## Decision Drivers

* **Operator self-service** — adding a provider is a Web UI task, not a redeploy.
* **Multiple providers active simultaneously** — different employee groups may live in different IdPs (e.g. internal Entra ID + external Auth0 for partners). Both should be selectable from the same login screen.
* **Lockout safety** — the local administrator path must remain available even if every external provider is broken.
* **Standards over bespoke flows** — we use OIDC Authorization Code + PKCE for OIDC, and SAML 2.0 Web Browser SSO for SAML. We do not implement either protocol ourselves.
* **Mature library reuse** — both protocols have load-bearing security details (nonce/state validation, signature verification, replay protection) we will not re-implement.
* **Compliance with ADR 0001** — providers are infrastructure adapters; the domain model only sees an abstract identity.

## Considered Options

### Option A — Pick one IdP family (e.g. OIDC only)

Implement OIDC + PKCE; tell operators with SAML-only IdPs to use a translator.

* (+) Smallest scope.
* (−) Real Japanese enterprise stacks frequently include SAML-only IdPs (especially older Active Directory Federation Services and shibboleth deployments). "Use a translator" is not a viable answer.
* (−) Locks the project out of common operator setups.

### Option B — Hard-wired multi-provider with config files

Both OIDC and SAML supported, configured by editing `config/auth.php`. No DB-backed registration.

* (+) Simpler than Web UI registration.
* (−) Conflicts with the project's "Web-first configuration" requirement.
* (−) Operators on shared hosting often cannot edit PHP config files without FTP and a redeploy.
* (−) Secret rotation requires file edits and restarts.

### Option C — Pluggable `AuthProvider` interface, providers registered in DB, configurable from the Web UI

Define `Saso\Domain\Auth\AuthProvider` with three concrete implementations: `LocalProvider`, `OidcProvider` (jumbojett/openid-connect-php), `SamlProvider` (onelogin/php-saml). An `auth_provider` table stores instances of those types. The login screen renders one button per enabled instance. Adding/editing/disabling a provider is an admin Web UI action; secrets are encrypted at rest and never displayed.

* (+) Operators can add an Auth0 tenant or a SAML IdP without redeploying.
* (+) Multiple instances of the same type are supported (e.g. two OIDC clients for two tenants).
* (+) The domain model only depends on the interface; storage, transport, and library choices stay in `Infrastructure/Auth/…`.
* (−) More moving parts: a table, a Web UI, an encryption scheme for client secrets.

## Decision Outcome

**Chosen option: C — Pluggable `AuthProvider` with DB-backed registration.**

### Interface (under `src/Domain/Auth/`)

```php
interface AuthProvider {
    public function id(): AuthProviderId;
    public function displayName(): string;
    public function beginLogin(LoginContext $ctx): RedirectResponse;       // → IdP
    public function completeLogin(CallbackRequest $req): AuthenticatedIdentity;
    public function supportsLogout(): bool;
    public function beginLogout(LogoutContext $ctx): RedirectResponse|null;
}
```

`AuthenticatedIdentity` carries `external_subject`, `email`, `display_name`, `claims` (raw), and the `auth_provider_id` that issued it. The domain knows nothing about OIDC or SAML.

### Implementations (under `src/Infrastructure/Auth/`)

* `LocalProvider` — wraps the existing Argon2id password flow.
* `OidcProvider` — wraps `jumbojett/openid-connect-php`. Uses Authorization Code + PKCE only. Supports OIDC Discovery (`.well-known/openid-configuration`).
* `SamlProvider` — wraps `onelogin/php-saml`. Web Browser SSO profile, AssertionConsumerService POST binding.

### Storage

```sql
CREATE TABLE auth_provider (
    id                       BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name                     VARCHAR(100) NOT NULL,           -- display name
    type                     ENUM('local','oidc','saml') NOT NULL,
    issuer_or_metadata_url   VARCHAR(500) NULL,
    client_id                VARCHAR(255) NULL,
    client_secret_encrypted  VARBINARY(1024) NULL,            -- AES-256-GCM via APP_KEY
    scopes                   VARCHAR(500) NULL,
    claim_mapping            JSON NULL,
    enabled                  TINYINT(1) NOT NULL DEFAULT 0,
    is_default               TINYINT(1) NOT NULL DEFAULT 0,
    created_at               DATETIME NOT NULL,
    updated_at               DATETIME NOT NULL
);

CREATE TABLE member_external_identity (
    member_id          BIGINT UNSIGNED NOT NULL,
    auth_provider_id   BIGINT UNSIGNED NOT NULL,
    external_subject   VARCHAR(255) NOT NULL,
    PRIMARY KEY (auth_provider_id, external_subject),
    UNIQUE KEY (member_id, auth_provider_id)
);
```

Multiple identities per member are allowed (e.g. an employee linked to both Entra ID and Google Workspace). The pair `(auth_provider_id, external_subject)` resolves to exactly one member.

### Web UI

* Admin screen lists all providers, their type, and an enabled toggle.
* Add/edit forms accept the provider URL (Discovery URL for OIDC, metadata URL or XML for SAML), client ID, client secret, scopes, and an optional claim-mapping JSON.
* Stored client secrets are AES-256-GCM-encrypted with `APP_KEY` (from `.env`). The UI shows `●●●` and a "Replace" button — never the plaintext.
* The login screen renders one button per `enabled = 1` provider, ordered by `is_default DESC, name ASC`.

### Lockout safety

1. **`bootstrap` administrator** — the installer creates a local admin account with the `bootstrap` role. This account cannot be demoted while it is the last `bootstrap` member. It always uses `LocalProvider`.
2. **`SAFE_MODE=true`** — when set in `.env`, the application disables every non-local provider regardless of the DB state. A misconfigured OIDC tenant cannot brick the instance.
3. **Provider health probe** — when an admin enables a provider, the UI runs a probe (Discovery fetch / metadata fetch) and refuses to enable on failure.

### User provisioning

* On first successful login from an external provider, a `Member` is created with `email`, `display_name`, and the external identity link.
* `claim_mapping` (JSON, e.g. `{"groups": "role"}`) drives initial role assignment from IdP claims.
* Subsequent logins update the email and display name from the IdP if they have changed (configurable per provider).

## Consequences

* Operators can wire SASO to Auth0, Cognito, Keycloak, Entra ID, Google Workspace, or any SAML 2.0 IdP without code changes.
* The domain model stays free of OIDC/SAML specifics; everything protocol-shaped lives in `Infrastructure/Auth/`. Tests for use cases that depend on identity inject a fake `AuthProvider`.
* Adding a third protocol later (e.g. WebAuthn for passkeys) means adding a fourth implementation of the same interface — no domain changes.
* Each provider is a separate dependency in `composer.json` (`jumbojett/openid-connect-php`, `onelogin/php-saml`). We accept the supply-chain surface; both are widely used and have a track record of timely security releases.
* Documentation under `docs/auth-providers/` (planned in M3-E) covers per-IdP setup with the exact fields the Web UI expects.
* This ADR does **not** decide the API authentication scheme for `/api/v1/*` (Bearer JWT vs. session cookie). That is left to a future ADR once the first authenticated endpoint is implemented.
