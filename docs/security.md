# Security

The full disclosure policy and operator hardening checklist live in [`SECURITY.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/SECURITY.md). This page summarizes the runtime posture established by the M1 hotfix milestone.

## What M1 fixed

| Finding | Pre-M1 | Post-M1 |
|---|---|---|
| Password hashing | SHA256 + global static salts × 1000 | `password_hash(PASSWORD_ARGON2ID)` with random per-row salts. Legacy rows upgrade transparently on next login. |
| CSRF token | `sha256(globalSalt + sessionId)` — identical for the whole session | 32-byte `random_bytes()` per session, verified with `hash_equals`, rotated on login |
| Session cookie | PHP defaults — no HttpOnly / Secure / SameSite | Set via `session_set_cookie_params()` before `session_start()` |
| HTTPS enforcement | `config.https=true` had no effect | 301 redirect + `Strict-Transport-Security: max-age=31536000; includeSubDomains` |
| DB credentials | Stored in `config.json` (committed with placeholders) | Overlay-able via `.env` (`DB_DSN`, `DB_USER`, `DB_PASSWORD`) |
| Upload validation | `$_FILES[..]['type']` (client-supplied) only | Six-step pipeline: `is_uploaded_file` + size cap + `finfo_file` + `getimagesize` allow-list |

See [`CHANGELOG.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/CHANGELOG.md) for the precise commits and PR links.

## Reporting a vulnerability

**Do not open a public issue.** Use one of:

- [**GitHub Private Vulnerability Reporting**](https://github.com/Willen-Federation/SASO-Willen-Edition/security/advisories/new) (preferred)
- Email — see [`SECURITY.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/SECURITY.md) for the contact channel

Response SLAs:

- **Acknowledgement** within 3 business days.
- **Initial triage** within 7 business days.
- **Status updates** every 14 days until resolution.
- **Coordinated disclosure** at 90 days from acknowledgement (or earlier if the fix ships sooner).

## Operator hardening

1. **Move secrets to `.env`** — never commit DB credentials or future OIDC client secrets.
2. **Set `APP_HTTPS=true`** once TLS is provisioned at the web-server layer. The application then 301-redirects HTTP and emits HSTS. The check honors `X-Forwarded-Proto` for reverse-proxy deployments.
3. **Restrict file permissions**: `.env` and `config.json` to `0640`. `.env` should never be web-readable.
4. **Run behind a reverse proxy or WAF** when the operational target permits it.
5. **Subscribe to security advisories** for this repository.

## Planned work

| Milestone | Item |
|---|---|
| **M3** | RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` codes; OIDC + SAML scaffold (operator-managed IdP registration) |
| **M4** | `paragonie/anti-csrf` replaces the deprecated `CSRFtoken::salting()` alias; `vlucas/phpdotenv` replaces the in-repo `EnvLoader`; AES-256-GCM encryption for `system_setting` secrets |
| **M5** | E2E coverage of the Playwright suite + signed release ZIPs |
