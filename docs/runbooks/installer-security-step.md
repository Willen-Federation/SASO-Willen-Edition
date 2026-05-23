# Runbook: installer security step — fresh-install secret generation

Last updated: 2026-05-23

## 1. Why this runbook exists

Before PR-A2, the install wizard rendered a "security step" page but did not
reliably write `APP_KEY` / `JWT_SECRET` / `WEBHOOK_SECRET` into `.env`. Fresh
installs that completed the wizard could end up with an empty `APP_KEY`,
causing `/api/v1/*` to return 500 with the error code `SASO-INFRA-9000`
("Refusing to boot with an all-zero AES key"). PR-A2 closes that gap.

This runbook documents what the new flow does, how to customise it, and how
to recover when something goes wrong.

For the related repair tool that addresses *existing broken installs*, see
[`repair-app-key.md`](repair-app-key.md). For fresh installs, the wizard now
handles secret generation automatically — the repair tool is only needed for
rotation and disaster recovery.

## 2. What the security step generates

The wizard's `Security` step writes three values to `.env`:

| Key              | What it is                          | How it's generated when blank |
|------------------|-------------------------------------|-------------------------------|
| `APP_KEY`        | AES-256-GCM master key (encrypts OAuth client secrets, etc.) | `base64_encode(random_bytes(32))` |
| `JWT_SECRET`     | HMAC secret for the mobile/MCP JWTs | `base64_encode(random_bytes(32))` |
| `WEBHOOK_SECRET` | Compared verbatim against `X-Webhook-Token` headers | `base64_encode(random_bytes(32))` |

Each value is validated against the same 3-shape rule the boot path uses
(`Saso\Infrastructure\Auth\Crypto\AppKeyResolver::tryResolve`):

- base64-encoded 32 bytes (44 chars with padding) — recommended
- hex-encoded 32 bytes (64 hex chars)
- any string of at least 32 characters (treated as a passphrase and run
  through SHA-256 at boot)

`WEBHOOK_SECRET` is held to the same ≥ 32 char rule but does not need to be
an AES-shaped key because it is compared as an opaque token rather than used
to derive a cipher key.

## 3. Providing custom values

The form lets expert operators paste their own values per key:

- Leave a field blank to let the installer generate a fresh
  `base64_encode(random_bytes(32))` value.
- Paste an existing value to reuse it (e.g. when migrating from another
  deployment or rolling out via configuration management). The value is
  validated against the 3-shape rule above and rejected with a clear error
  if it is too short.
- Tick **既存の値があっても再生成する** ("regenerate even when existing
  values are valid") to rotate the keys. **This invalidates anything
  encrypted with the previous `APP_KEY`** — only use it intentionally.

## 4. Preflight checks

Before rendering any wizard step, the installer runs a preflight gate
({@see `installer/Preflight.php`}). It asserts:

| Check ID            | Asserts                                          | Common remedy when it fails |
|---------------------|--------------------------------------------------|-----------------------------|
| `random_bytes`      | `random_bytes()` is callable                     | Rebuild PHP with CSPRNG enabled (default on every supported PHP build) |
| `env_dir_exists`    | The directory that will contain `.env` exists    | `mkdir -p <dir>` |
| `env_dir_writable`  | PHP can write into that directory                | `sudo chown <php-user> <dir>` + `sudo chmod u+w <dir>` |
| `env_file_writable` | If `.env` already exists, PHP can write to it    | `sudo chown <php-user> .env` + `sudo chmod u+w .env` |

If any check fails the wizard renders a dedicated **インストール前提条件
エラー** page that lists each failure with a copy-pasteable `chmod`/`chown`
command. The wizard **cannot proceed** until preflight is green — this is
the gate that prevents silently broken installs.

## 5. Post-install self-test

After the admin user is created and just before `installer/installer.json`
would be deleted, the wizard runs a final self-test
({@see `installer/PostInstallSelfTest.php`}). It:

1. Re-reads `.env` from disk.
2. Validates `APP_KEY` through `AppKeyResolver::tryResolve()` — the exact
   call `src/Presentation/Api/V1/Bootstrap.php` will make on the first real
   request.
3. Validates `JWT_SECRET` and `WEBHOOK_SECRET` against their shape rules.
4. (Optional) Issues a `cURL` GET to `/api/v1/health` and
   `/api/v1/auth/providers` against the local host and asserts each returns
   a 2xx status. The HTTP probe is best-effort and short-timeout (3s connect,
   5s total) so it does not hang the wizard on environments where the API
   is reachable only through a reverse proxy.

If any assertion fails, `installer.json` is **not deleted** and the wizard
renders an inline error on the "completed" page with a link back to the
security step. The operator can re-submit the security step (which will
re-validate and re-write under the same rollback guarantees) or use
`tools/repair-app-key.php` to recover.

## 6. Troubleshooting

### 6.1 Preflight fails on `env_dir_writable`

```bash
# Inspect the current permissions
ls -ld <dir>
# Expected: drwx------ <php-user> <php-user>

# Fix
sudo chown <php-user> <dir>
sudo chmod u+w <dir>
```

The `<php-user>` is shown verbatim in the preflight failure page — usually
`www-data` (Debian/Ubuntu), `apache` (RHEL), or a custom php-fpm pool user.

### 6.2 The security step rejects my pasted APP_KEY

The value must satisfy one of:

- `printf '%s' "$VAL" | base64 -d | wc -c` is exactly 32, or
- `printf '%s' "$VAL"` is 64 chars of `[0-9a-fA-F]`, or
- `printf '%s' "$VAL" | wc -c` is at least 32.

Generate a known-good value with `openssl rand -base64 32` and paste that.

### 6.3 Self-test fails on the "installed" page

This means the `.env` was written but a re-read rejects one of the values
— normally impossible because we validate before writing. If it happens:

1. Click **セキュリティステップに戻る** ("back to security step") and
   resubmit.
2. If that still fails, run the repair tool from the shell:
   ```bash
   php tools/repair-app-key.php
   ```
   See [`repair-app-key.md`](repair-app-key.md) for full details.

### 6.4 Self-test fails on the HTTP probe

The wizard surfaces the status code and cURL error. Common causes:

- The API endpoint is not yet wired to the same `.env` (php-fpm reload
  pending) — `sudo systemctl reload php8.2-fpm` and refresh.
- A reverse proxy is intercepting requests — the wizard hits
  `http://127.0.0.1/...` by default. Skip the HTTP segment by removing the
  URLs from the call site, or run the curl smoke test manually:
  ```bash
  curl -is http://127.0.0.1/api/v1/health
  ```

### 6.5 I need to rotate keys after install

The installer is one-shot; for rotation use `tools/repair-app-key.php`:

```bash
php tools/repair-app-key.php --key=app_key --force
```

See [`repair-app-key.md`](repair-app-key.md) for the full rotation runbook.

## 7. Related

- [`repair-app-key.md`](repair-app-key.md) — repair tool for existing
  installs and post-rotation recovery.
- [`2026-05-saso-infra-9000.md`](2026-05-saso-infra-9000.md) — incident
  report that motivated PR-A1 / PR-A2.
- `installer/SecurityStep.php` — orchestrator covered by this runbook.
- `installer/Preflight.php` — preflight gate implementation.
- `installer/PostInstallSelfTest.php` — post-install self-test
  implementation.
- `src/Infrastructure/Auth/Crypto/AppKeyResolver.php` — the shared
  boot-time validator that both the installer and the repair tool
  delegate to.
