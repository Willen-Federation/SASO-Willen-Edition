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
- **Secrets out of `config.json`**: `ConfigLoader` now overlays a sibling
  `.env` file on top of `config.json` for `DB_DSN` / `DB_USER` /
  `DB_PASSWORD` / `APP_HTTPS` (resolution: `.env` → real env var →
  `config.json`). The overlay is opt-in: deployments without `.env` keep the
  pre-M1 behavior. `.env` is git-ignored; `.env.example` ships as the
  template.
- **Image upload validation hardened**. `util/UploadValidator` replaces the
  client-supplied `$_FILES['type']` check with a multi-step inspection of the
  uploaded bytes: `is_uploaded_file()` to reject caller-supplied paths,
  `finfo_file()` for the real MIME, an explicit byte ceiling (5 MiB by
  default), and `getimagesize()` to reject polyglots and HTML/SVG payloads
  that pass a sniff but are not decodable images. `image/AddController` now
  consumes the validator's `Either<{tmp_name, mimeType, size, extension}>`
  result so a rejected upload short-circuits before any DB write.

### Added
- **Local development stack** (M2-C). New `docker/Dockerfile`
  (`php:8.2-apache` + `pdo_mysql` / `gd` / `zip` / `intl` / `mbstring` /
  `opcache` / `exif` + Composer 2), `docker/apache/000-default.conf`
  (`AllowOverride All` so the project's `.htaccess` is honored),
  `docker/php/saso.ini` (dev-tuned overrides), `docker/mariadb/init.sql`
  (UTF-8 collation), and `docker-compose.yml` (`app` + `mariadb:10.6` +
  `adminer:4`, plus an optional `--profile sso` Keycloak service for OIDC /
  SAML testing). Apple Silicon compatibility via `platform: linux/amd64`,
  bind-mount `:cached` hint for macOS file performance, and a
  health-checked DB so `app` waits for MariaDB. `.dockerignore` keeps the
  build context small.
- **Project `Makefile`** with targets for the full development loop:
  `make up` / `make down` / `make install` / `make migrate` / `make test` /
  `make analyse` / `make cs-check` / `make cs-fix` / `make qa` /
  `make shell` / `make db-shell` / `make logs` / `make ps` / `make help`.
  Targets are idempotent and use `-T` exec for clean piped output.
- **Test, lint, and static-analysis tooling** (M2-B). `phpunit.xml.dist`
  configures three suites (Unit / Integration / Feature) with strict
  PHPUnit 10.5 settings (`failOnRisky`, `failOnWarning`,
  `beStrictAboutOutputDuringTests`). `phpstan.neon.dist` runs at level 6
  scoped to files this fork owns (`src/`, `tests/`, the three M1 utility
  rewrites). `.php-cs-fixer.dist.php` enforces PSR-12 plus project
  conventions (short array syntax, ordered imports, single quotes,
  trailing commas) on the same scope. `tests/bootstrap.php` registers
  a minimal autoloader for the legacy `saso\\` namespace so tests can
  exercise existing classes without booting the full ConfigLoader
  chain. **52 unit tests** ship for `Either`, `Member`,
  `CSRFtoken`, `EnvLoader`, and `UploadValidator` (pre-`is_uploaded_file`
  steps). CI gains three jobs — `cs-check`, `static-analysis`,
  `unit-tests` (PHP 8.2 + 8.3 matrix, with Composer caching) — alongside
  the existing syntax / JSON / composer-validate jobs.
- **Composer foundation** (M2-A). New `composer.json` declares PHP 8.2+,
  required ext-* extensions, and PSR-4 autoload `Saso\\` → `src/` for new
  Clean-Architecture code. Dev dependencies — `phpunit/phpunit`,
  `phpstan/phpstan`, `friendsofphp/php-cs-fixer`, `roave/security-advisories`
  — are declared but their config files land in M2-B. `composer.lock` is
  committed for reproducibility. CI gains a `composer-validate` job that
  runs `composer validate --strict` and a lock-consistency dry-run install
  (which also blocks vulnerable dependencies via roave/security-advisories).
- **Autoload bridge** in `index.php`: requires `vendor/autoload.php` first
  when present, then registers the existing `ClassLoader` for the legacy
  `saso\\` namespace. Both autoloaders coexist so new code under `src/` and
  legacy code under the repository root resolve correctly without conflicts.
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

<!--
  Compare/release links will be added once v2.4.x is tagged in this fork.
  The pre-fork upstream history above is included for context only.
-->

