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
- **Pluggable IdP scaffold** (M3-E). Establishes the contract recorded
  in [ADR 0003](docs/architecture/adr/0003-pluggable-idp.md) so the
  concrete `LocalProvider` / `OidcProvider` / `SamlProvider` adapters
  and the `auth_provider` admin UI can land in M4 against a stable
  surface. New `Saso\Domain\Auth\AuthProvider` interface defines the
  full lifecycle (`beginLogin` → IdP → `completeLogin` →
  `AuthenticatedIdentity`, plus `supportsLogout` / `beginLogout`).
  Value objects: `AuthProviderId`, `AuthProviderType` (enum:
  local/oidc/saml — values match the future DB column verbatim),
  `AuthenticatedIdentity` (subject + email + display name + raw claims),
  `LoginContext` (return URL + state + nonce), `CallbackRequest`
  (read-only HTTP envelope), `LogoutContext`, `Redirect` (3xx + URL),
  and `ClaimMapping` (operator-configurable map from IdP claim names
  to SASO `Member` fields, OIDC defaults baked in). Typed exceptions
  `AuthFailedException` (factories: `invalidCredentials`,
  `stateMismatch`, `callbackInvalid`) and
  `ProviderMisconfiguredException` wired to three new error codes:
  `SASO-AUTH-1006` (provider misconfigured, 503), `SASO-AUTH-1007`
  (callback state mismatch, 400), `SASO-AUTH-1008` (callback
  validation failed, 400). All three ship with EN + JA translations.
  `Saso\Infrastructure\Auth\Crypto\SecretEncryptor` is the AES-256-GCM
  authenticated-encryption primitive for OIDC client secrets and SAML
  private keys at rest. Wire format `[1-byte version | 12-byte IV |
  N-byte ciphertext | 16-byte tag]` lets us rotate the algorithm
  without bricking already-stored secrets. The 32-byte key is the
  application's `APP_KEY`; `generateKey()` produces a fresh one for
  the installer. Composer dependencies installed and ready for M4:
  `jumbojett/openid-connect-php` ^1.0, `onelogin/php-saml` ^4.1.
  **38 new unit tests** (183 total): `AuthProviderId` validation,
  `AuthProviderType` enum invariants, `AuthenticatedIdentity` field
  storage and empty-subject rejection, `ClaimMapping` defaults +
  overrides + missing-claim safety, `Redirect` URL/status validation,
  `AuthFailedException` / `ProviderMisconfiguredException` factory
  semantics across the three new error codes, and a 12-case
  `SecretEncryptor` suite covering round-trip, IV uniqueness,
  ciphertext / tag tampering, wrong-key rejection, version-byte
  rejection, key-length validation, empty plaintext, and 50-call key
  uniqueness. New `docs/auth-providers/index.md` plus a sidebar entry
  (EN + JA nav translation) document the contract and stake out where
  the per-IdP setup guides will live.
- **REST API surface with OpenAPI 3.1 as source of truth** (M3-D). New
  `config/openapi.yaml` is the canonical contract for `/api/v1/*` —
  paths, methods, schemas, and an `x-handler` extension that names the
  PHP callable per operation (cf. ADR 0002). `composer require
  nikic/fast-route:^1.3`. The new `Saso\Presentation\Api\V1\Router`
  reads the spec at boot, builds a fast-route dispatcher from it, and
  refuses to start if the handler map omits an `operationId` declared
  in the YAML — schema/code drift is detected at construction, not at
  runtime. `Saso\Presentation\Api\V1\OpenApiSpec` is the parser
  (loads from disk or YAML string), `HttpRequest`/`JsonResponse`/
  `RawResponse` are minimal request/response value objects (PSR-7
  deferred until a real need surfaces). Three meta endpoints ship in
  this PR: `GET /api/v1/health` (liveness probe — `{status, version,
  time}`, never touches the database), `GET /api/v1/openapi.yaml`
  (serves the spec verbatim), `GET /api/v1/docs` (embedded Swagger UI
  loaded from unpkg). `Saso\Presentation\Api\V1\Bootstrap` is the
  composition root; `index.php` short-circuits to it whenever
  `REQUEST_URI` starts with `/api/v1/`, so the legacy router (PHP
  screens) is untouched. Two new error codes ship for routing
  failures: `SASO-INFRA-9003` (route not found, 404) and
  `SASO-INFRA-9004` (method not allowed, 405). Both come with EN +
  JA translations and dedicated `RouteNotFoundException` /
  `MethodNotAllowedException` factories under
  `src/Domain/Shared/Exception/`. **24 new unit tests** (145 total)
  cover spec parsing, route extraction, request parsing,
  JSON/raw response encoding, controller behaviour, router dispatch
  (FOUND, NOT_FOUND, METHOD_NOT_ALLOWED, handler exception → Problem
  Details, missing-handler boot rejection), and end-to-end Problem
  Details correlation through the router. CI bumps PHPStan's memory
  limit to 512M (the OpenAPI/translation graph crosses 1.12's default
  128M ceiling on the GitHub-hosted runner).
- **i18n with Symfony Translation + bilingual catalogue** (M3-C).
  `composer require symfony/translation:^7.2 symfony/yaml:^7.2`. New
  `Saso\Infrastructure\Translation\Translator` adapts Symfony's contract
  with an explicit `$fallback` parameter so missing keys do not surface
  raw catalogue paths to clients. `TranslatorFactory` discovers
  `translations/<locale>.yaml` files automatically and wires the
  English fallback locale chain. `TranslatorRegistry` holds the
  process-wide instance for boundary surfaces (templates, the legacy
  view layer until it migrates) and is the backing service for the
  global `__()` helper, autoloaded via Composer's `files` block.
  `Saso\Presentation\Http\I18n\LocaleResolver` picks the request locale
  from `?lang=` → member preference → `Accept-Language` (with q-value
  parsing and primary-subtag matching) → configured default. The
  `ProblemExceptionHandler` now accepts an optional `Translator` and
  resolves `error.<code>.title|detail` against the request locale,
  with `{traceId}` placeholder interpolation for `SASO-INFRA-9000`.
  `translations/en.yaml` and `translations/ja.yaml` ship the full M3-B
  catalogue (8 codes × title + detail). **33 new unit tests** (121
  total) cover translator semantics, fallback chains, registry
  lifecycle, the global helper, locale resolution edge cases, and the
  handler's translator integration.
- **RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` error catalogue** (M3-B).
  New `Saso\Domain\Shared\ErrorCode` enum (8 initial cases across `AUTH` and
  `INFRA`) is the canonical, append-only catalogue; each case carries
  `httpStatus()`, `domain()`, `translationKey()`, and `defaultTitle()`.
  `Saso\Domain\Shared\DomainException` is the abstract base every typed
  exception extends — it carries an `ErrorCode` plus a structured `context`
  array that is logged but never serialised to clients.
  `Saso\Presentation\Http\Problem\ProblemDetails` is the immutable RFC 7807
  value object (with `code` + `traceId` vendor extensions);
  `ProblemRenderer` encodes / emits `application/problem+json` bodies;
  `ProblemExceptionHandler` is the single termination point — `DomainException`
  becomes its own code, anything else becomes `SASO-INFRA-9000` with a fresh
  `traceId` and a generic message (or the original message if `debug=true`).
  Monolog 3.10 ships as a production dependency; `MonologFactory` builds the
  application logger with a `RotatingFileHandler` (14 daily files under
  `var/log/`) and a `TraceIdProcessor` that promotes `traceId` from `context`
  into `extra` so it shows up uniformly across line-formatter and structured
  sinks. `docs/error-codes.md` is now a populated catalogue, cross-linked
  from [ADR 0004](docs/architecture/adr/0004-rfc7807-problem-details.md).
  **36 new unit tests** (88 total) cover the enum invariants, exception
  semantics, UUIDv4 generation, `application/problem+json` shape, handler
  branching, and processor wiring.
- **ADRs 0001-0004** (M3-A). MADR-format records of the four load-bearing
  M3 architecture decisions: Clean Architecture + DDD layout under `src/`
  via Strangler Fig migration; OpenAPI 3.1 as the single source of truth
  for `/api/v1/*`; Pluggable IdP via the `AuthProvider` interface
  (OIDC + SAML + Local) with DB-backed registration and AES-256-GCM-encrypted
  client secrets; RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` codes with
  `code` and `traceId` extensions. ADR index page reorganised into Accepted
  vs. Planned tables; the four ADRs are exposed in the MkDocs sidebar.
- **Bilingual developer documentation site** (M2-D). New `mkdocs.yml`
  configures Material for MkDocs with the `mkdocs-static-i18n` plugin
  (suffix mode: `foo.md` for English default, `foo.ja.md` for Japanese
  override), `mkdocs-mermaid2-plugin`, `mkdocs-git-revision-date-localized`,
  and `mkdocs-minify-plugin`. The docs tree under `docs/` ships scaffold
  pages for Home / Getting Started (with full bilingual installation
  walkthrough) / Configuration / Architecture / ADR Index / Development
  (Workflow + Testing) / Security / API Reference / Error Codes /
  Changelog. Pages without a Japanese sibling fall back to English. A new
  `.github/workflows/docs.yml` runs `mkdocs build --strict` on every PR
  touching `docs/`, `mkdocs.yml`, or `requirements.txt`, and on `main`
  pushes deploys to GitHub Pages at
  https://willen-federation.github.io/SASO-Willen-Edition/.
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

