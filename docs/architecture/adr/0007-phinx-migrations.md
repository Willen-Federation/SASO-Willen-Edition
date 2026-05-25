# 0007 — Phinx for schema migrations

* Status: accepted
* Date: 2026-04-26
* Deciders: Willen Federation contributors

## Context and Problem Statement

M1 shipped a single migration file (`migrations/M1_001_widen_password_column.sql`) and a `migrations/README.md` documenting "apply by hand". M3 added two more (`config/openapi.yaml` is _not_ a migration but moved in adjacent territory; `feature_flag`, `auth_provider`, `member_external_identity`, `system_setting` all need to land in M4). With four bounded contexts about to add tables in M4 and beyond, hand-applied SQL stops scaling: there is no idempotency guard, no versioning, no order, and no story for shared-hosting deployments where SSH access is unreliable.

We need a migration toolchain that:

1. tracks which migrations have run and refuses to re-apply them;
2. runs from the command line **and** from a Web context (some shared-hosting operators have no SSH);
3. produces reversible migrations where reversibility is cheap; non-reversible migrations are an explicit choice, not an oversight;
4. ships in PHP (matches the rest of the toolchain — operators do not need a second runtime to deploy);
5. composes with the Phinx ecosystem (templates, conventions, IDE awareness);
6. costs nothing in production performance — migrations are dev/deploy-time, not request-time.

## Decision Drivers

* **Web-runnable** — the M5 installer (a Web flow on shared hosting) must be able to run pending migrations.
* **Explicit version table** — every deploy produces an auditable list of "what schema state is this database in?".
* **PHP-native** — no Java, no Ruby, no Python. The Composer dependency surface is already big enough.
* **Rollback discipline** — destructive migrations declare it; reversible ones are reversible.
* **Compliance with ADR 0001** — migrations live next to the bounded context they belong to (`migrations/Auth/`, `migrations/Item/`, …) once the bounded contexts physically move into `src/`.

## Considered Options

### Option A — Continue hand-applied SQL files

* (+) Zero dependencies.
* (−) No idempotency. Operators run a file twice and the second run errors out (or silently corrupts state).
* (−) No version table. "What schema state is this database in?" requires reading every file in `migrations/` and comparing to the `information_schema`.
* (−) Cannot run from a Web context safely.

### Option B — Doctrine Migrations

The most popular PHP migration tool. Composer install, plenty of plugins.

* (+) Mature. Active community.
* (−) Doctrine DBAL is a heavy dependency to pull in just for migrations; we use raw PDO everywhere else and have no plans to adopt Doctrine ORM.
* (−) The CLI is opinionated about Symfony Console wiring and configuration discovery. Wiring it into a Web installer is more work than the alternative.

### Option C — Phinx

A standalone migration tool by CakePHP authors. Composer install, no ORM dependency, supports MySQL/MariaDB/PostgreSQL/SQLite, has a published Web-runnable mode (`Phinx\Wrapper\TextWrapper`).

* (+) PDO under the hood — no DBAL, no ORM coupling.
* (+) Web-runnable via the wrapper, which is exactly what the M5 installer needs.
* (+) Migration files are standalone classes; tests can instantiate them in isolation.
* (+) Mature: in production at large operators for ~10 years.
* (−) Less mindshare than Doctrine Migrations in the wider PHP ecosystem.

### Option D — Bare-PDO migrator we write ourselves

* (+) Zero dependency.
* (−) Reinventing version tracking, lock acquisition, the up/down convention, and the Web wrapper. Phinx already does these.

## Decision Outcome

**Chosen option: C — Phinx.**

### Layout

```
phinx.php                           # tool config — paths, environments, version table
migrations/
├── M1/
│   └── 20260101000001_widen_password_column.php
├── M3/
│   └── ...
└── M4/
    ├── 20260426120000_create_system_setting.php
    ├── 20260426120001_create_system_setting_audit.php
    ├── 20260426120002_create_auth_provider.php
    ├── 20260426120003_create_member_external_identity.php
    ├── 20260426120004_create_feature_flag.php
    ├── 20260426120005_create_error_log_aggregate.php
    └── 20260426120006_create_feature_flag_audit.php
seeds/
└── M4/
    └── DefaultSystemSettings.php   # baseline rows the installer (M5) seeds
```

The legacy `migrations/M1_001_widen_password_column.sql` is rewritten as a Phinx class and the SQL file is deleted.

### Configuration

* The Phinx version table is `phinx_log` (the default). It persists per-environment.
* `phinx.php` declares a single `production` environment that reads the same `.env` keys (`DB_DSN` / `DB_USER` / `DB_PASSWORD`) the application uses. This guarantees migrations talk to the same database as the runtime.
* The Phinx CLI is installed via Composer dev dependencies (`robmorgan/phinx`); the M5 installer ships it as part of the `vendor/`-bundled ZIP.

### Web wrapper

The M5 installer shells out to Phinx via `Phinx\Wrapper\TextWrapper`. Existing Web-runnable installer scaffolding (`installer/`) gains a step:

```php
$wrapper = new TextWrapper($app);
$result  = $wrapper->getMigrate();      // runs pending migrations
```

The output is rendered into the installer page. Failure halts the wizard and surfaces the migration error.

### Conventions

1. **One migration per concern.** A migration that creates a table also adds the indexes that table needs to be useful. A migration that adds a column does not also rename an unrelated one.
2. **Reversible by default.** If a migration drops data, it MUST set `up()` and `down()` such that `down()` raises `IrreversibleMigrationException`. We never forget that the migration was destructive.
3. **No data backfills in migrations longer than ~30 seconds.** Long backfills get a separate one-shot script under `scripts/` and a flag on the migration that skips the backfill in production deployments.
4. **Tests run against the same migrations.** The integration suite (M4) provisions a fresh test database via `phinx migrate -e testing` before each run, so "the schema in CI matches what an operator will get".
5. **Bounded contexts get sub-directories** once the M4 physical migration moves them into `src/`. Until then, `migrations/M4/`, `migrations/M5/`, etc. are organised by milestone.

### What we are not adopting

* **Phinx's built-in seed runner** is fine for a small set of bootstrap rows (default `system_setting` values, the `bootstrap` admin role); we use it for those. We do **not** use it for fixture data — fixtures live in the test suite and are loaded through the application's repositories so they stay in sync with code-level invariants.
* **Phinx's plugin system** — we have no use for it now. If we adopt one later, ADR will record the choice.

## Consequences

* `composer require --dev robmorgan/phinx` (M4-B implements). Production deployments install via the bundled `vendor/` (M5).
* Operators run `bin/phinx migrate` on the CLI, or — on shared hosting — let the M5 installer's Web wrapper drive it.
* Schema state is auditable: `phinx_log` answers "what version is this database at?".
* Migrations are class-based PHP, so the test suite can run them and assert on the resulting schema (we already have a unit-test suite; the integration suite added in M4 will exercise these).
* Three legacy migration files (M1 + M3 helpers) get rewritten as Phinx classes in M4-B. The hand-applied SQL files are removed in the same PR; pre-M4 deployments are advised in the M4 release notes to apply both migration sets in order.
* Reversibility discipline is enforced in code review, not by the tool. We accept this — destructive migrations are rare enough that documenting "this is irreversible" by hand is fine.
