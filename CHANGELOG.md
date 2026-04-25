# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security
- **Migrate password hashing from a custom SHA256 chain to Argon2id**
  (`password_hash(PASSWORD_ARGON2ID)`). `Member::verifyPassword()` accepts both
  the legacy and the new format, and `LoginUsecase::maybeRehash()` upgrades
  legacy rows to Argon2id transparently on a successful login. Existing users
  are not signed out. Hardcoded global salts (`stok-administra_sistemo` /
  `plej_simpla`) are no longer used for new writes.
- `repository/member/FindOneByAuth` now looks up by id only and returns the
  stored hash; password equivalence is checked in PHP via `password_verify()`,
  so the digest no longer flows through SQL parameters.
- **CSRF tokens are now session-bound random 32-byte values** generated via
  `random_bytes()` and stored in `$_SESSION`. `CSRFtoken::verify()` uses
  constant-time `hash_equals` comparison. Replaces the previous deterministic
  `sha256(globalSalt + sessionId)` scheme which yielded the same token for an
  entire session and depended only on a single repository-wide salt.
- The CSRF token is rotated on login (`LoginView::display()`), and
  `session_regenerate_id(true)` now drops the prior session file so a stolen
  pre-login id cannot be reused.
- **HTTPS enforcement** in `index.php`: when `config.https` is `true` the
  application 301-redirects HTTP to HTTPS (honoring `X-Forwarded-Proto` for
  reverse-proxy deployments) and emits the
  `Strict-Transport-Security: max-age=31536000; includeSubDomains` header.
- **Session cookie hardening** via `session_set_cookie_params()` before
  `session_start()`: `HttpOnly` (always), `SameSite=Lax` (always), and
  `Secure` (when `config.https` is `true`).

### Added
- English-first bilingual `README.md` with quick-start, requirements, and roadmap.
- `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md` for open-source community readiness.
- `.gitignore`, `.editorconfig` for consistent developer environments.
- `.htaccess` scaffold for `mod_rewrite` configuration.
- GitHub Actions workflow with PHP syntax linting (`php -l`).
- Pull-request and issue templates under `.github/`.
- `migrations/` directory with `M1_001_widen_password_column.sql` to widen
  `Member.password` from VARCHAR(80) to VARCHAR(255) for Argon2id digests.

### Changed
- Repository now positioned as a globally maintained open-source project (English documentation primary, Japanese fully supported).
- `installer/createTables.php` creates `Member.password` as `VARCHAR(255)`.

### Removed
- `Member::hashed()` (the SHA256 chain) is no longer publicly callable. Its
  logic is preserved as a `private static` for legacy verification only and
  will be removed entirely once the migration window closes.

---

## Historical (pre-fork)

The following entries are from the original SASO project by Japan Standards Organization (日本標準機構). See [`ORIGINAL_README.md`](ORIGINAL_README.md) for the full Japanese-language history.

### [2.4.0] — 2023-10-09
- Major update: full code rewrite, UX improvements, security hardening.

### [2.3.4] — 2019-02-10
- Documentation improvements.

### [2.3.3] — 2016-11-22
- Bug fixes.

### [2.3.2] — 2016-11-21
- Bug fixes.

### [2.3.1] — 2015-11-10
- Bug fixes.

### [2.3.0] — 2013-12-19
- Initial public release.

[Unreleased]: https://github.com/Willen-Federation/SASO-Willen-Edition/compare/v2.4.0...HEAD
[2.4.0]: https://github.com/Willen-Federation/SASO-Willen-Edition/releases/tag/v2.4.0
