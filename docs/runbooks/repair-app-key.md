# Runbook: `tools/repair-app-key.php` — recover from missing/invalid APP_KEY

Last updated: 2026-05-23

## 1. When to use

Run this tool when **any of the following** is true on a deployment:

| Symptom | Likely cause |
|---|---|
| `GET /api/v1/auth/providers` returns 500 with `SASO-INFRA-9000` | `APP_KEY` missing or shorter than 32 chars |
| `GET /api/v1/items` returns 500 (not 401) on any request, even valid JWTs | `JWT_SECRET` and `APP_KEY` both missing — boot fails closed |
| `POST /webhook` returns 500 with `SASO-INFRA-9000` instead of 401 | `WEBHOOK_SECRET` missing |
| `php-error.log` contains "Refusing to boot with an all-zero AES key" | `APP_KEY` empty in `.env` |
| Fresh install completed via `/installer/start` but `/api/v1/*` returns 500 | Installer does not yet generate `APP_KEY` — fixed in PR-A2 (see [`installer-security-step.md`](installer-security-step.md)) |

> **For fresh installs:** the installer's security step (PR-A2) generates
> all three secrets automatically and runs a post-install self-test that
> blocks completion if any secret would fail boot validation. This tool
> exists for *rotation* and *recovery* of already-installed servers — it
> is not part of the normal install flow. See
> [`installer-security-step.md`](installer-security-step.md) for the
> fresh-install path.

The tool is **safe to run repeatedly**: existing valid values are preserved.
It is also **safe to run on an already-working server**: nothing changes if
all three keys (`APP_KEY`, `JWT_SECRET`, `WEBHOOK_SECRET`) already pass
validation.

## 2. Quick reference

```bash
# Show what would change without writing anything
php tools/repair-app-key.php --dry-run

# Apply the changes
php tools/repair-app-key.php

# Repair only one key
php tools/repair-app-key.php --key=app_key

# Forcibly rotate even when current value is valid (e.g. suspected leak)
php tools/repair-app-key.php --key=app_key --force

# Help
php tools/repair-app-key.php --help
```

Exit codes:

| Code | Meaning |
|---|---|
| 0 | All targeted keys are valid (after any write) |
| 1 | A write failed or self-verification rejected the value |
| 2 | Invalid CLI flags |

## 3. What the tool does

For each target key (`APP_KEY`, `JWT_SECRET`, `WEBHOOK_SECRET` by default):

1. **Read** the current value from `.env` via the same parser used at boot
   (`util/EnvLoader.php`).
2. **Validate**:
   - `APP_KEY` and `JWT_SECRET` are accepted as base64-encoded 32 bytes,
     hex-encoded 32 bytes, or any string of at least 32 characters — the
     same three shapes accepted by `Bootstrap::encryptorKey()` /
     `AppKeyResolver::tryResolve()`.
   - `WEBHOOK_SECRET` is accepted at ≥ 32 chars (matches `.env.example`).
3. **Decide**:
   - Value valid and `--force` not set → preserve, print `[OK]`.
   - Value missing/invalid OR `--force` set → generate a fresh
     `base64_encode(random_bytes(32))` and write it.
4. **Back up** the existing `.env` to `.env.backup.YYYYMMDD-HHMMSS` (chmod
   0600) on the first mutation of each run, so you can roll back.
5. **Write atomically** via `util/EnvWriter::setOrUpdate()`:
   - Writes to a per-process temp file (`.env.tmp.<pid>.<rand>`).
   - Holds an exclusive `flock()` on `.env.lock` for the read-modify-write
     window.
   - `rename()` the temp file into place (atomic on POSIX same-fs).
   - `chmod 0600` and, if running as root, `chown` to the file's prior
     owner.
6. **Self-verify** by passing the new value through `AppKeyResolver::tryResolve()`
   (for APP_KEY) and an equivalent ≥ 32-char check (for JWT_SECRET); prints
   `[OK] APP_KEY validated: base64-32B` on success.
7. **Summary table** at the end:

```
Summary:
  APP_KEY          changed
  JWT_SECRET       preserved
  WEBHOOK_SECRET   changed
```

## 4. Production deployment

The tool is designed for the production server `saso.sksl.jp`. Follow this
sequence on a fresh deploy or after diagnosing `SASO-INFRA-9000`:

```bash
# 1. SSH to the server and cd into the SASO document root.
ssh saso.sksl.jp
cd /var/www/saso  # or whatever the document root is

# 2. Pull the latest code (must include this PR).
git pull --ff-only

# 3. Dry-run first — confirm the plan.
php tools/repair-app-key.php --dry-run

# 4. Apply.
php tools/repair-app-key.php

# 5. Verify file ownership matches the web user (php-fpm / Apache).
#    The tool best-effort chowns to the .env's existing owner when running
#    as root. If you ran it as your shell user, check:
ls -l .env
# expected: -rw------- www-data www-data .env   (or your php-fpm user)
# if wrong:
sudo chown www-data:www-data .env

# 6. Reload php-fpm so the running workers re-read the .env on next request.
sudo systemctl reload php8.2-fpm

# 7. Smoke test from outside.
curl -is https://saso.sksl.jp/api/v1/auth/providers | head -1
# → HTTP/2 200
```

If `chown` is required (step 5), prefer running the tool itself as root via
`sudo php tools/repair-app-key.php` — the tool detects that case and
preserves ownership automatically.

### 4.1 Rollback

If anything goes wrong after a write:

```bash
# List backups (newest first).
ls -lt .env.backup.*

# Restore the most recent one.
cp .env.backup.20260523-184500 .env
chmod 0600 .env
chown www-data:www-data .env
sudo systemctl reload php8.2-fpm
```

The tool **never deletes** backups. Operators are responsible for pruning
old `.env.backup.*` files when they are no longer needed.

## 5. Why this is separate from the installer (relationship to PR-A2)

PR-A1 shipped this **standalone repair tool**. PR-A2 layered the same
generation + validation logic into the installer's security step so a
freshly installed server boots with valid secrets without ever needing this
tool. See [`installer-security-step.md`](installer-security-step.md) for the
fresh-install path.

In other words:

- **PR-A1**: fix existing broken deployments + provide a tool for future
  emergencies.
- **PR-A2**: prevent the broken-deployment situation from happening in the
  first place on fresh installs (preflight + auto-generate + post-install
  self-test).

The repair tool continues to exist after PR-A2 because:

- Operators may rotate `APP_KEY` periodically (compliance, suspected
  leak) and need a safe path that doesn't require hand-editing `.env`.
- Long-lived deployments that pre-date PR-A2 may still have empty values
  at upgrade time.
- Disaster recovery (restoring from a partial backup that omitted `.env`).

## 6. Caveats

- **APP_KEY rotation invalidates encrypted data.** If you regenerate
  `APP_KEY` (intentionally with `--force` or unintentionally because the
  old value was missing), any rows in `auth_provider.client_secret_encrypted`
  that were encrypted with the old key become undecryptable. See
  [`docs/runbooks/2026-05-saso-infra-9000.md`](2026-05-saso-infra-9000.md)
  Section 5 for how to recover.
- **JWT_SECRET rotation invalidates active mobile sessions.** Issued JWTs
  carry no key ID; any device with an active token will get 401 and need
  to re-pair via `/m/issue-pairing`. This is intentional — fail closed.
- The tool does **not** restart php-fpm itself. The shell-level
  `systemctl reload php8.2-fpm` step is required for in-flight workers
  to see the new `.env`.

## 7. Related

- `util/EnvWriter.php` — the hardened writer the tool uses.
- `util/EnvLoader.php` — the parser whose behaviour the writer matches.
- `src/Infrastructure/Auth/Crypto/AppKeyResolver.php` — the boot-time
  validator the tool delegates to for self-verification.
- `src/Presentation/Api/V1/Bootstrap.php:280-306` — the boot path that
  fails closed when `APP_KEY` is invalid.
- `docs/runbooks/2026-05-saso-infra-9000.md` — incident report that
  motivated PR-A1 / PR-A2.
