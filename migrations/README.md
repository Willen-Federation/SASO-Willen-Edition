# Database Migrations

Pre-M2 hand-applied SQL files. Numbered `M{milestone}_{nnn}_{slug}.sql`.

Apply each pending migration **once** against existing deployments **before**
deploying the corresponding application code. Fresh installations created via
[`installer/`](../installer/) already include the latest schema and skip these
files.

## How to apply

```bash
mysql -u <user> -p <database> < migrations/M1_001_widen_password_column.sql
```

Or via cPanel / phpMyAdmin: paste the contents into the SQL editor and run.

## Tracking

A formal migration runner (Phinx or similar) ships in **M2** along with
Composer. Until then, operators must keep their own log of which files have
been applied. Each file is idempotent where feasible.

## Index

| File | Milestone | Required for | Status |
|------|-----------|--------------|--------|
| [`M1_001_widen_password_column.sql`](M1_001_widen_password_column.sql) | M1 | Argon2id password migration | new |
