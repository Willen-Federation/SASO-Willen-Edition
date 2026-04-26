# 0006 — `system_setting` DB table editable from the admin Web UI

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

Today the application reads operational knobs from `config.json` plus a `.env` overlay (M1). Some are deployment shape (DSN, HTTPS toggle), others are runtime preferences (default locale, label sheet size, mail SMTP host). The line between "secret" and "preference" is blurred, so operators on shared hosting end up either editing JSON via FTP or touching `.env` to change something as banal as the default rows on a label sheet.

We need a configuration model that:

1. lets operators edit non-secret runtime preferences from a Web UI **without** editing files;
2. keeps secrets out of the database — or, when they must live there, encrypts them at rest;
3. makes precedence between `.env`, real environment variables, `config.json`, and the new DB-backed settings deterministic and reviewable; and
4. survives "who changed this and when?" questions.

## Decision Drivers

* **Web-first configuration** — explicit user requirement #11.
* **Secret/preference separation** — DB rows are visible to anyone with read access to MySQL; some configuration must never land there.
* **Precedence clarity** — a setting present in two sources must always resolve the same way.
* **Audit** — every UI-driven change should be reconstructable from the database.
* **Compliance with ADR 0001** — domain code consumes a `SystemSettingService` interface; the DB adapter lives in `Infrastructure/`.

## Considered Options

### Option A — Continue to use `config.json` exclusively

* (+) No new code paths.
* (−) Editing JSON via FTP is the actual user-experience problem we are trying to solve. Failing this requirement leaves the project's M0 UX intact, which is exactly what M4 set out to change.

### Option B — Move everything to `.env`

* (+) Single source of truth.
* (−) Operators on shared hosting often cannot edit `.env` without re-uploading via FTP either.
* (−) `.env` is a deploy-time mechanism, not a runtime one. Editing it from a Web UI defeats its purpose (and the convention that `.env` is on disk and git-ignored).

### Option C — `system_setting` DB table editable from a Web UI; `.env` keeps the secrets and deploy shape

A `system_setting(key, value, value_type, encrypted, updated_at, updated_by)` table holds the runtime-tunable knobs. Reads go through a `SystemSettingService`; writes go through the admin Web UI and write a `system_setting_audit` row. Secrets that live in DB (`OIDC_CLIENT_SECRET`, etc.) are encrypted with `Saso\Infrastructure\Auth\Crypto\SecretEncryptor` (M3-E) using the application's `APP_KEY`. Anything in `.env` (`APP_KEY`, `DB_PASSWORD`, `DB_DSN`) takes precedence over the DB.

* (+) Operators edit preferences from the UI; they never need shell access to change a label-sheet size or a default locale.
* (+) Secret/preference separation is explicit: secrets are encrypted, and any setting that resides in `.env` overrides the DB row.
* (+) Audit trail is one extra table, written by the admin endpoint.
* (−) Two configuration sources to read; precedence rules must be documented and tested.

## Decision Outcome

**Chosen option: C — `system_setting` table for UI-editable runtime preferences; `.env` for secrets and deploy-shape values; deterministic precedence.**

### Tables

```sql
CREATE TABLE system_setting (
    `key`        VARCHAR(120) PRIMARY KEY,
    value        BLOB NOT NULL,
    value_type   ENUM('string','int','bool','json','secret') NOT NULL,
    encrypted    TINYINT(1) NOT NULL DEFAULT 0,
    updated_at   DATETIME NOT NULL,
    updated_by   VARCHAR(120) NOT NULL                            -- member id or 'installer'
);

CREATE TABLE system_setting_audit (
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `key`        VARCHAR(120) NOT NULL,
    old_value    BLOB NULL,                                       -- never raw secrets; encrypted blobs only
    new_value    BLOB NULL,
    changed_by   VARCHAR(120) NOT NULL,
    changed_at   DATETIME NOT NULL,
    reason       VARCHAR(500) NULL,
    KEY idx_key_changed (`key`, changed_at)
);
```

### Precedence (highest → lowest)

1. **`.env` file** — secrets and deploy shape (`APP_KEY`, `DB_*`, `APP_HTTPS`, `SAFE_MODE`). Always wins. Never goes through the UI; never appears in audit logs.
2. **Real environment variable** — `getenv($name)`. Wins over DB. Lets containerised deployments inject configuration without writing `.env` files.
3. **`system_setting` row** — runtime preferences. Editable from the UI. Audited.
4. **`config.json`** — installer-emitted defaults. Read-only at runtime. The installer (M5) uses it as a bootstrap source; operations layer never writes it.
5. **Hard-coded default** — last-ditch fallback baked into the consuming class.

### What goes where

| Setting | Source | Notes |
|---|---|---|
| `APP_KEY` | `.env` only | Encryption key — must never appear in DB or in `config.json`. |
| `DB_DSN`, `DB_USER`, `DB_PASSWORD` | `.env` only | If the DB is unreachable, you cannot read settings from it. |
| `APP_HTTPS` | `.env` (preferred) or `system_setting` | Operator may flip via UI once TLS is provisioned. |
| `SAFE_MODE` | `.env` only | Lockout escape hatch (cf. ADR 0003). Must work without DB. |
| `default_locale` | `system_setting` | UI-editable preference. |
| `outputRow`, `sheetAmount` | `system_setting` | Label-printing defaults. |
| `mail.smtp_host`, `mail.smtp_port` | `system_setting` | Operator-tunable. |
| `auth.mode` (`local` / `oidc` / `saml` / `all`) | `system_setting` | Coexists with the per-provider `enabled` flag from ADR 0003. |
| OIDC client secrets, SAML private keys | `auth_provider.client_secret_encrypted` | Per-provider, encrypted with `SecretEncryptor`. Not a `system_setting`. |

### Web UI

* Dedicated `/admin/settings` screen with one form per category. Inputs are typed (text / number / checkbox / textarea-for-JSON). Updating a row writes the audit table in the same transaction.
* Settings stored as `value_type=secret` render as `●●●` and a "Replace" button. The plaintext is never re-emitted from the server, even to admins.
* `.env`-shadowed settings show with a banner: "This value is currently overridden by `.env`. UI edits are stored in the DB but will not take effect until the `.env` entry is removed."

### Service layer

```php
namespace Saso\Domain\Setting;

interface SystemSettingService
{
    public function get(string $key): ?SettingValue;
    public function getString(string $key, ?string $default = null): ?string;
    public function getBool(string $key, ?bool $default = null): ?bool;
    public function getInt(string $key, ?int $default = null): ?int;

    /** @throws SettingNotFoundException */
    public function require(string $key): SettingValue;

    public function set(string $key, SettingValue $value, string $changedBy, ?string $reason = null): void;
}
```

The PDO adapter implements caching scoped to a single request — every read after the first is in-memory. UI writes invalidate the cache for the touched key.

### Migration of legacy `config.json` keys

* M4 ships a one-shot script `scripts/migrate_config_to_settings.php` that reads `config.json` and writes corresponding `system_setting` rows for any key not already present.
* `config.json` continues to be read on first installation (legacy installer in M5 still writes it). Once the migration script runs, the operator can delete or shrink `config.json`; the UI takes over.

## Consequences

* Operators edit preferences from a single admin screen; FTP is no longer required for routine configuration changes.
* Secrets stay in `.env` (or, when they must be in DB, encrypted via `SecretEncryptor` from M3-E). The `.env` precedence rule guarantees that a misuse of the admin UI cannot weaken a secret.
* The audit table makes "who changed `default_locale` last Tuesday" answerable with a single SQL query.
* Reading a setting now consults two sources in a defined order. The service layer encapsulates this so call sites stay simple.
* Schema additions: `system_setting` and `system_setting_audit`. Both are small and indexed for the queries they serve.
* The `auth_provider` and `feature_flag` tables (ADRs 0003 + 0005) live alongside but are not subsumed — those have richer per-row semantics that don't belong in a generic key/value store.
