# Database Migrations

Schema changes ship as [Phinx](https://book.cakephp.org/phinx/0/en/index.html) class-based migrations (cf. [ADR 0007](../docs/architecture/adr/0007-phinx-migrations.md)). The legacy hand-applied `.sql` workflow was retired in M4-B.

## Layout

```
migrations/
├── M1/                       # M1 hot-fix migrations (Argon2id column widening)
├── M4/                       # M4 schema additions (system_setting, auth_provider, …)
└── README.md
seeds/                        # Phinx seed classes (default rows, bootstrap admin role, …)
phinx.php                     # tool config (project root)
```

Each milestone gets its own sub-directory. Within a milestone, files are named `<14-digit timestamp>_<snake_case_slug>.php` and the class name matches the slug in `CamelCase`.

## Running migrations

### Locally (Docker dev stack)

```bash
make migrate           # apply all pending migrations to the dev DB
make migrate-status    # show what's applied vs pending
```

### Against a non-Docker target

```bash
vendor/bin/phinx migrate -e production
vendor/bin/phinx status -e production
```

The `production` environment in [`phinx.php`](../phinx.php) reads the same `.env` keys the application uses (`DB_DSN` / `DB_USER` / `DB_PASSWORD`) — Phinx and the runtime always talk to the same schema.

### From the M5 Web installer

The installer (lands in M5) wraps Phinx via `Phinx\Wrapper\TextWrapper` so shared-hosting operators without SSH can run pending migrations from the install wizard.

## Conventions

| Rule | What it means |
|---|---|
| **One concern per file** | A migration that creates a table also adds the indexes it needs to be useful, but does not also rename an unrelated column. |
| **Reversible by default** | `up()` and `down()` are both implemented when reversing the change is cheap. Destructive migrations declare `throw new IrreversibleMigrationException(...)` from `down()` — never an empty `down()`. |
| **No long backfills inline** | Backfills longer than ~30 s live in `scripts/<name>.php` and are launched separately. Migrations stay quick so deploys do not time out. |
| **Class names mirror the file slug** | `20260101000001_widen_password_column.php` ↔ `class WidenPasswordColumn`. Phinx checks this. |
| **Bounded context awareness** | Once M4-G physically moves bounded contexts into `src/`, migrations follow into `migrations/<context>/` (`migrations/Auth/`, `migrations/Item/`). Until then, milestone sub-directories are the organising unit. |

## What runs in CI

The integration test suite (added in M4) provisions a fresh test database via `phinx migrate -e testing` before each run, so the schema CI exercises is the schema operators get.

## Index

| File | Milestone | Purpose | Status |
|---|---|---|---|
| [`M1/20260101000001_widen_password_column.php`](M1/20260101000001_widen_password_column.php) | M1 | Argon2id column widening | applied to existing deployments before M1 release |

The full set for M4 (system_setting, auth_provider, member_external_identity, feature_flag, error_log_aggregate, feature_flag_audit) lands in M4-C/D/E.
