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
- **`plugin_registry` storage tier** (M6-J1). Establishes the
  persistence half of the plugin lifecycle contracted by
  [ADR 0015](docs/architecture/adr/0015-plugin-system.md). The
  Composer-based discovery loop (`PluginDiscovery` + `PluginContext`
  facade with the six typed registries) lands in M6-J2 against this
  stable contract.

  Migration (`migrations/M6/`):
  * `20260427120000_create_plugin_registry.php` — `package` (unique),
    `class`, `name`, `version`, `activated_at`, `deactivated_at`,
    `last_seen_at`, `settings_json` JSON. Indexed on `(package)` and
    `(deactivated_at)` for the active-plugin list. Reversible.

  Domain layer (`src/Domain/Plugin/`):
  * `Plugin` — marker interface (`metadata()` only in this PR; full
    lifecycle methods `register` / `activate` / `deactivate` add in
    M6-J2 alongside the discovery loop).
  * `PluginMetadata` — readonly value object. Validates Composer-style
    `vendor/name` package format, non-empty name + version, default
    `versionCompat = '*'`.
  * `PluginRecord` — immutable view of one row. Validates `id ≥ 1`,
    Composer-style package, non-empty class. `isActive()` predicate;
    `withDeactivatedAt()` and `withLastSeenAt()` non-mutating
    mutators.
  * `Repository/PluginRepository` interface — `findByPackage` /
    `findById` / `listActive` / `listAll` / `activate` / `markSeen` /
    `deactivate`.

  Infrastructure layer (`src/Infrastructure/Plugin/`):
  * `PdoPluginRepository` — concrete PDO impl. `activate()` is an
    upsert: a new row gets the current timestamp; an existing row
    has its `deactivated_at` cleared and `class` + `version` refreshed
    (plugin authors sometimes move their entry class between
    releases) while the original `activated_at` is preserved so audit
    history survives re-activation. JSON-encodes `settings_json` on
    write, decodes on read.

  `phinx.php` extends its `migrations` paths to include
  `migrations/M6` so `make migrate` picks up the new file.

  Tests (15 new, 386 total): `PluginMetadataTest` (6 — full
  invariants + default versionCompat), `PluginRecordTest` (6 — id /
  package validation, `isActive` flip, non-mutating mutators),
  `PdoPluginRepositoryTest` (9 — find-on-missing, activate inserts +
  re-reads, re-activation refreshes class/version while preserving
  id, re-activation clears `deactivated_at`, `listActive` filters,
  `listAll` ordered by name, `markSeen` updates `last_seen_at`,
  `deactivate` sets timestamp, `settings_json` round-trip),
  `CreatePluginRegistryTest` (4 — file path, class name, extends
  `AbstractMigration`, is final).
- **ADRs 0015-0016** (M6-A2). Two strategic architecture decisions
  added in response to user follow-up requests:
  * **0015 Plugin system** — Composer-discovered packages with typed
    extension-point registries (`AuthProviderRegistry`,
    `AiAssistantRegistry`, `McpToolRegistry`, `DomainEventBus`,
    `ApiRouteRegistry`, `SystemSettingService`). Plugins ship as
    Composer packages whose `composer.json` declares
    `extra.saso.plugin.class` — SASO walks
    `vendor/composer/installed.json` once at boot, instantiates each
    plugin, and asks it to register against the typed registries.
    Lifecycle (install / activate / register / deactivate / uninstall)
    is recorded in a new `plugin_registry` table; `SAFE_MODE=true`
    skips every plugin so a misbehaving third-party cannot brick the
    instance. Plugins cannot replace core providers (collisions
    rejected on canonical names) — only add new ones.
  * **0016 English as default locale + extract legacy JA strings** —
    flips the configured-default locale to `en` for new installs;
    existing JA operators are unaffected because the `LocaleResolver`
    chain still picks JA when `default_locale = ja` (`system_setting`)
    or `Member.locale = ja`. A one-shot extraction script walks the
    legacy templates, finds JA literals, proposes translation keys,
    and emits `git apply`-able patches. Per-template extraction PRs
    land in M6-A3 onwards. CI gains an
    `audit_untranslated_strings.php` check that fails when a new
    template introduces a JA literal without a `__()` call. PDF
    labels stay JA-only by default; a `report.locale` system_setting
    flips them to EN once the bundled `IPAex` font is confirmed.

  ADR index page extends the Accepted table to 0015-0016;
  `mkdocs.yml` nav exposes both files in the Architecture sidebar
  (EN + JA rendering verified via `mkdocs build --strict`). Total
  architecture decisions on record: 15 accepted (0001-0007 +
  0009-0016), 1 planned (0008, M5).
- **AI gateway scaffold** (M6-B). Establishes the vendor-agnostic
  contract recorded in
  [ADR 0009](docs/architecture/adr/0009-multi-llm-gateway.md).
  Concrete OpenAI / Gemini / Claude adapters land in M6-F against
  this stable surface.

  Domain layer (`src/Domain/Ai/`):
  * `AiAssistant` interface — `chatComplete` / `extractStructured` /
    `embed` / `describeImage`. Documented exception contract.
  * `ChatMessage` + `ChatRole` enum (system/user/assistant/tool —
    values match OpenAI / Anthropic / Gemini wire format verbatim).
  * `ChatRequest` with constructor-time invariants (non-empty
    messages, temperature in [0, 2], maxTokens ≥ 1, valid
    `responseFormat`, `jsonSchema` required for `json_schema`).
  * `ChatResponse` with `decoded()` helper.
  * `AiUsage` token-count value object.
  * `EmbeddingTask` enum (retrieval.query / retrieval.passage /
    clustering / classification / similarity).
  * `EmbeddingRequest` (text + image inputs, optional dimensions
    override) and `EmbeddingResponse` (uniform-dim vectors with
    `dimensions()` accessor + ragged-vector rejection).
  * `ImageRequest` + `ImageDescriptionResponse`.
  * `StructuredExtractionRequest` + `StructuredExtractionResponse`
    for the M6-G barcode → structured-extraction pipeline.
  * Five typed exceptions wired to the new `SASO-AI-8001..8005`
    error codes (`AiProviderNotConfigured` 503, `AiRateLimited`
    429, `AiResponseMalformed` / `AiContextExceeded` /
    `AiContentPolicy` 422). Plus `AiUpstreamException` mapped to
    `SASO-INFRA-9000` for transient 5xx / network failures so the
    M6-F `FallbackChainAssistant` can identify retryable
    operations.

  Infrastructure layer (`src/Infrastructure/Ai/`):
  * `NullAssistant` — every method throws
    `AiProviderNotConfiguredException`. Selected by the M6-F
    composition root when no API key is configured for the
    operation, or when `SAFE_MODE=true`.

  Five new error codes ship with EN + JA translations.

  Tests (47 new, 346 total): `ChatMessageTest` (3),
  `ChatRequestTest` (7), `ChatResponseTest` (3),
  `EmbeddingRequestTest` (5), `EmbeddingResponseTest` (4),
  `ImageRequestTest` (4), `StructuredExtractionRequestTest` (5),
  `AiExceptionTest` (6), `NullAssistantTest` (5).
  `ErrorCodeTest` data provider extended with the five new codes.
- **ADRs 0009-0014** (M6-A). Strategic architecture decisions for the
  M6 scope expansion (AI / vector search / Flutter mobile / MCP). All
  six accepted up-front so the implementation PRs (M6-B onwards) land
  against a stable contract.
  * **0009 Multi-LLM gateway** — `Saso\Domain\Ai\AiAssistant` interface
    with `OpenAiAssistant` / `GeminiAssistant` / `ClaudeAssistant` /
    `NullAssistant` implementations. Per-feature provider selection
    via `system_setting` (`ai.provider.chat`, `…embedding`, `…vision`).
    `FallbackChainAssistant` decorator for failover. API keys stored
    encrypted via `SecretEncryptor` (M3-E). New error-code range
    `SASO-AI-8xxx`. `SAFE_MODE=true` forces `NullAssistant`.
  * **0010 Vector search via OpenSearch** — k-NN plugin handles both
    image / text similarity and BM25 full-text scoring in one engine.
    Two indices: `saso_items` (with `text_embedding` + `image_embedding`
    knn_vector fields whose dim is configurable) and
    `saso_storage_locations`. `NullSearchIndex` fallback uses MariaDB
    LIKE when OpenSearch is unreachable.
  * **0011 Flexible item attributes + storage location codes** —
    EAV pattern (`item_attribute_definition` + `item_attribute_value`)
    so operators add attributes from the admin UI without schema
    migrations. Hierarchical `storage_location` table with a
    deterministic code generator. `similar_item` link table populated
    offline from OpenSearch k-NN.
  * **0012 Search + cache infrastructure** — Redis 7 (single
    container) handles cache, AI rate limiting, optional session
    store, and the Symfony Messenger transport (ADR 0013). Read-through
    `Cache` interface with `RedisCache` and `NullCache`; `SAFE_MODE`
    forces null. Operators on shared hosting without Redis stay on
    the null path.
  * **0013 Symfony Messenger queue** — PHP-native equivalent of
    Sidekiq. Redis Streams transport when Redis is configured,
    Doctrine on MariaDB otherwise. Standard retry policy (3 retries,
    exponential backoff, dead-letter). Handlers in
    `Application/Messaging/Handler/`, messages in
    `Domain/Messaging/Message/`, wiring in
    `Infrastructure/Messaging/`. New `make messenger-consume` target.
  * **0014 Flutter pairing + MCP server** — RFC 8628 OAuth 2.0 Device
    Authorization Grant for mobile pairing (QR code on PC + polling
    from phone). `device_token` table with sha256-hashed tokens, named
    devices, scoped permissions, revoke from admin UI. MCP endpoint
    served from the same PHP app under `/mcp` (JSON-RPC 2.0); tools
    pluggable via `McpTool` interface; per-tool `enabled` toggle in
    `system_setting`. New error codes `SASO-AUTH-1009/1010/1011`
    (pairing) and a fresh `SASO-MCP-Axxx` namespace.

  ADR index page promotes 0009-0014 to Accepted; `mkdocs.yml` nav
  exposes the six new files in the Architecture sidebar. Total
  architecture decisions on record: 13 accepted (0001-0007 + 0009-0014),
  1 planned (0008 vendor-bundled release ZIP, M5). `mkdocs build
  --strict` passes for both EN and JA renders.
- **`feature_flag` + `error_log_aggregate` + `feature_flag_audit`
  storage tier** (M4-E1). Establishes the database half of the
  OpenFeature integration contracted by
  [ADR 0005](docs/architecture/adr/0005-openfeature-with-db-provider.md).
  The OpenFeature `DbProvider`, the `error_log_aggregate` writer wired
  into `ProblemExceptionHandler`, and the cron `CircuitBreaker` script
  land in M4-E2 against this stable contract.

  Migrations (`migrations/M4/`):
  * `20260426120004_create_feature_flag.php` — operator-managed
    flag rows: `key_name` (unique, indexed), `description`,
    `enabled`, `rollout_percent` (0-100, TINYINT UNSIGNED),
    `conditions` JSON for targeting rules, `error_threshold` +
    `error_window_min` for the circuit-breaker policy
    (`error_threshold = 0` means "never auto-disable"),
    `auto_disabled_at` + `auto_disable_reason` for breaker
    diagnostics.
  * `20260426120005_create_error_log_aggregate.php` — per-flag error
    counts in time buckets. No FK to `feature_flag.key_name` —
    the writer runs synchronously from the global exception handler
    and a stale flag delete must not block error logging. Indexed
    on `(feature_key, window_start)` for the breaker sweep.
  * `20260426120006_create_feature_flag_audit.php` — append-only
    history of every toggle. `flag_key` denormalised so audit rows
    survive flag deletion; `changed_by` is either a member id, the
    literal `circuit_breaker`, or `installer` for bootstrap rows.

  All three migrations reversible.

  Domain layer (`src/Domain/Feature/`):
  * `FeatureKey` — readonly, format-validated (1-120 chars,
    lowercase alphanumeric + `.`/`_`). Mirrors LaunchDarkly /
    GrowthBook / ConfigCat naming so flag keys port cleanly.
  * `FeatureFlag` — full aggregate with constructor invariants
    (`id >= 1`, non-empty description, `0 <= rolloutPercent <= 100`,
    `errorThreshold >= 0`, `errorWindowMinutes >= 1`). Named
    mutators: `withEnabled(bool)` for admin toggles,
    `tripBreaker(at, reason)` for circuit-breaker events. Both
    return fresh aggregates so the value is safe to share between
    request-scoped caches.
  * `FeatureFlagAuditEntry` — readonly view with
    `isCircuitBreakerEvent()` predicate.
  * `Repository/FeatureFlagRepository` — `findByKey` /
    `findById` / `listAll` / `save` / `delete`.
  * `Repository/ErrorLogAggregateRepository` — `recordError` (hot
    path) / `countSince` (read by the breaker) /
    `purgeOlderThan` (retention).
  * `Repository/FeatureFlagAuditRepository` — `record` /
    `listForFlag` (newest-first, limit-bounded). No update/delete
    — audit is append-only.
  * `Exception/FlagNotFoundException` typed to a new
    `SASO-FLAG-7001` (404) error code with EN + JA translations.

  Infrastructure layer (`src/Infrastructure/FeatureFlag/`):
  * `PdoFeatureFlagRepository` — concrete PDO impl. JSON-encodes
    `conditions` on write, decodes on read. Re-reads the row after
    `save()` so callers always get the persisted shape.
  * `PdoErrorLogAggregateRepository` — each error becomes a one-row
    "tick" with `count = 1`; the breaker sums over the window. We
    do not bucket on write because clock skew across application
    hosts makes shared bucket boundaries fragile — exact ticks +
    window-end column means the sweep query stays correct.
  * `PdoFeatureFlagAuditRepository` — single INSERT per write;
    `listForFlag` returns newest-first with a stable secondary sort
    on `id DESC` so sub-second resolution doesn't randomise order.

  SQL portable across MariaDB and SQLite (test substrate).

  Tests (44 new, 299 total): `FeatureKey` (7 — format invariants),
  `FeatureFlag` (10 — full storage, breaker mutator, all five
  constructor invariants), `FeatureFlagAuditEntry` (2),
  `FlagNotFoundException` (3 — error code wiring, key in context,
  message includes key), `CreateFeatureFlagTest` (4 — three-file
  migration smoke check), `PdoFeatureFlagRepositoryTest` (7 —
  find-on-missing, save + re-read, save updates in place,
  conditions JSON round-trip, breaker state round-trip, listAll
  alpha order, delete), `PdoErrorLogAggregateRepositoryTest` (5 —
  empty count, record + count, window filter, key filter, purge
  returns row count and prunes correctly),
  `PdoFeatureFlagAuditRepositoryTest` (5 — record + listForFlag
  round-trip, newest-first ordering, key filtering, limit, breaker
  event detection). `ErrorCodeTest` data provider extended with
  the new code.
- **`auth_provider` + `member_external_identity` storage tier** (M4-D).
  Establishes the database half of the Pluggable IdP design contracted
  by [ADR 0003](docs/architecture/adr/0003-pluggable-idp.md). Two
  Phinx migrations: `auth_provider` (operator-managed IdP
  registrations — `name`, `type`, `issuer_or_metadata_url`,
  `client_id`, AES-256-GCM-encrypted `client_secret_encrypted`,
  `scopes`, `claim_mapping` JSON, `enabled`/`is_default` flags) and
  `member_external_identity` (the link table from `Member` to one or
  more IdP-issued subjects, with composite PK
  `(auth_provider_id, external_subject)` plus a unique
  `(member_id, auth_provider_id)` index, and a `last_login_at` column
  for "stale identity" reports). Both reversible.

  Domain layer (`src/Domain/Auth/`):
  * `AuthProviderRecord` — readonly value object mirroring one row.
    Carries the **plaintext** `clientSecret`; the repository
    encrypts/decrypts at the storage boundary so callers never deal
    with ciphertext directly. `withEnabled(bool)` returns a copy with
    the toggle flipped (admin UI uses this when an operator clicks
    "disable").
  * `ExternalIdentity` — readonly value object. Validates that
    `memberId >= 1` and `externalSubject` is non-empty.
  * `Repository/AuthProviderRepository` interface — `findById` /
    `listAll` / `listEnabled` / `save` / `delete`. Implementations
    return rows ordered as the login screen expects them
    (`is_default DESC, name ASC`).
  * `Repository/ExternalIdentityRepository` interface — `find` by
    `(providerId, externalSubject)`, `listForMember`, `link`,
    `recordLogin`, `unlink`.

  Infrastructure layer (`src/Infrastructure/Auth/Repository/`):
  * `PdoAuthProviderRepository` — concrete PDO impl. Reads decrypt
    the `client_secret_encrypted` blob via `SecretEncryptor` (M3-E)
    before constructing a record; writes encrypt on the way back.
    The plaintext does not appear in any log line. JSON-encodes
    `claim_mapping` on write and decodes on read. Re-reads the
    record after `save` so callers always get the persisted shape.
  * `PdoExternalIdentityRepository` — straightforward CRUD with
    composite-PK enforcement on `link()` and a `recordLogin()` that
    bumps `last_login_at` + `updated_at` in one statement.

  SQL is portable across MariaDB (production) and SQLite (test
  substrate); the repos round-trip through both.

  Tests (16 new, 255 total):
  * `AuthProviderRecordTest` (2) — full-field storage,
    `withEnabled` returns a non-mutating copy.
  * `ExternalIdentityTest` (4) — full-field storage,
    member-id and external-subject validation, optional
    `lastLoginAt`.
  * `CreateAuthProviderTest` (4) — migration smoke checks for both
    `auth_provider` and `member_external_identity`.
  * `PdoAuthProviderRepositoryTest` (8) — find-on-missing, save +
    re-read round-trip, secret encrypted at rest (begins with the
    `\x01` SecretEncryptor version byte), null secret round-trips
    as null, save updates existing row in place (no duplicate
    inserts), `listAll` ordered by default-first then alpha,
    `listEnabled` filters disabled rows, claim-mapping round-trip,
    delete.
  * `PdoExternalIdentityRepositoryTest` (6) — find-on-missing, link
    + find round-trip, list-for-member excludes other members,
    `recordLogin` sets `last_login_at`, unlink, composite PK
    enforced (second link with same `(providerId, sub)` raises).
- **`system_setting` storage tier** (M4-C). Establishes the database
  half of the configuration model contracted by
  [ADR 0006](docs/architecture/adr/0006-system-setting-web-ui.md). Two
  Phinx migrations (`migrations/M4/20260426120000_create_system_setting.php`
  + `..._audit.php`) create the `system_setting` table (PK `key`,
  enum `value_type`, encrypted-blob value, audit columns) and the
  append-only `system_setting_audit` history. `down()` is reversible
  for both — settings tier is recoverable until M4-D wires it into the
  admin UI.

  Domain layer (`src/Domain/Setting/`):
  * `SettingKey` — value object with format validation (1-120 chars,
    alphanumeric + `.`/`_`/`-`); rejects spaces, slashes, oversize.
  * `SettingType` — backed enum (`string`/`int`/`bool`/`json`/`secret`)
    matching the DB column verbatim. `isSecret()` predicate.
  * `SettingValue` — typed wrapper with constructor-time format
    validation (Bool requires `0`/`1`, Int requires base-10, Json
    requires valid JSON). Named factories: `string()`, `int()`,
    `bool()`, `json()`, `secret()`. Accessors: `asString()` /
    `asInt()` / `asBool()` / `asJson()`.
  * `SystemSettingService` interface — `get` / `require` / `set` /
    `delete` / `all`. Storage-tier contract; the wider precedence
    chain (`.env` → env var → DB → `config.json` → default) is
    composed at the consumer layer.
  * `Exception/SettingNotFoundException` typed to the new
    `SASO-CONFIG-6001` (404) error code with EN + JA translations.

  Infrastructure layer (`src/Infrastructure/Setting/`):
  * `PdoSystemSettingService` — concrete PDO implementation. Reads
    are cached for the lifetime of the service instance (= one
    request); writes invalidate the cached key. Secrets are
    encrypted at rest via `SecretEncryptor` (M3-E); the audit row
    stores ciphertext, never plaintext, so rotating `APP_KEY` does
    not retroactively expose the audit history. SQL is portable
    enough to run on both MariaDB and SQLite — the upsert is a
    SELECT-then-INSERT-or-UPDATE rather than `ON DUPLICATE KEY
    UPDATE`, so the same code path exercises in both environments.

  Tests (42 new, 230 total): SettingKey (7) — format constraints,
  empty/oversize/space/slash rejection, equality. SettingType (3) —
  enum invariants, `isSecret`. SettingValue (11) — string/int/bool/
  secret factories, JSON round-trip, JSON encoder failure on
  resources, raw-constructor format rejection across Bool/Int/Json.
  SettingNotFoundException (3) — error code wiring, key in context,
  message includes key. CreateSystemSetting (4) — migration class
  smoke checks. PdoSystemSettingService (14) — full lifecycle:
  get-on-missing returns null, require-on-missing throws, round-trip
  for every non-secret type, secret encrypted at rest with
  transparent decryption on read, audit shape on insert + update,
  audit stores ciphertext for secrets, delete + audit, delete-on-
  missing is a no-op, `all()` returns every row keyed by name,
  request-scoped cache (returns stale data after sibling write,
  invalidates on the service's own write, remembers proven absence).
- **Phinx-based schema migrations** (M4-B). Replaces the hand-applied
  SQL workflow established in M1 with class-based migrations driven by
  [Phinx](https://book.cakephp.org/phinx/0/en/index.html) (cf.
  [ADR 0007](docs/architecture/adr/0007-phinx-migrations.md)).
  `composer require --dev robmorgan/phinx:^0.16`. New `phinx.php` at the
  project root exposes a `production` environment that reads
  `DB_DSN` / `DB_USER` / `DB_PASSWORD` through the same `.env` chain
  the application uses, plus a `testing` environment for CI integration.
  Migrations live under `migrations/<milestone>/` (M1, M4, …) so each
  delivery line gets its own sub-directory; seed classes live under
  `seeds/`. The legacy
  `migrations/M1_001_widen_password_column.sql` is rewritten as
  `migrations/M1/20260101000001_widen_password_column.php` (class
  `WidenPasswordColumn`); `down()` raises
  `IrreversibleMigrationException` to prevent a narrow-rollback from
  silently truncating Argon2id hashes. New Makefile targets:
  `make migrate`, `make migrate-status`, `make migrate-rollback`,
  `make seed`. PHPStan's `scanDirectories` includes `migrations/` so
  the class symbols are visible to reflection-based tests without
  forcing the migrations through level-6 analysis. New 5-case smoke
  test (`WidenPasswordColumnTest`) verifies file path, class name
  matches the slug, extension of `AbstractMigration`, finality, and
  one-way `down()`. **188 unit tests** total. `migrations/README.md`
  is rewritten as a Phinx operations guide (run locally / production /
  M5 Web installer; conventions; CI integration). The five
  conventions from ADR 0007 are spelled out in the README.
- **ADRs 0005-0007** (M4-A). MADR-format records of the three
  load-bearing M4 architecture decisions: OpenFeature PHP SDK + a
  SASO-owned `feature_flag` DB provider with cron + tail-of-request
  fallback circuit breaker (`feature_flag` / `error_log_aggregate` /
  `feature_flag_audit` schema sketched, evaluation pipeline through
  the OpenFeature client interface so call sites stay decoupled);
  `system_setting` DB table editable from the admin Web UI with a
  documented precedence chain (`.env` → real env var →
  `system_setting` → `config.json` → hard-coded default), secrets
  encrypted at rest via the M3-E `SecretEncryptor` and `.env`-shadowed
  rows banner-flagged in the UI; Phinx for schema migrations with the
  `phinx_log` version table, a Web wrapper for the M5 installer, and
  five conventions covering atomicity, reversibility, backfill scope,
  test integration, and bounded-context layout. ADR index page
  promotes 0005-0007 from Planned to Accepted; the seven ADRs are
  exposed in the MkDocs sidebar.
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

