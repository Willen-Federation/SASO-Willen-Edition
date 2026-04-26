# Installation

This page covers the **web installer** wizard reachable at `/installer/start`. The installer creates the application schema and the initial administrator account, then locks itself by writing `installer/installer.json`.

## Before you begin

- An **empty MariaDB / MySQL database** with a user that has `CREATE`, `INSERT`, `SELECT`, `UPDATE`, `DELETE`, `ALTER` privileges.
- The repository (or the release ZIP) deployed under your DocumentRoot.
- `.env` populated with `DB_DSN`, `DB_USER`, `DB_PASSWORD` (recommended) **or** the same values placed in `config.json` under `database.*`.
- `.htaccess` `RewriteBase` adjusted to match the install path.

## Walkthrough

1. **Open `/installer/start`** in a browser. The page does an environment self-check (PHP version, required extensions) before letting you proceed.
2. **Confirm DB connectivity.** The installer attempts the DSN you supplied. Failure messages show the underlying PDO error so you can correct credentials or grants.
3. **Create the schema.** Tables are created via `installer/createTables.php`. Existing tables are not dropped — re-running aborts safely.
4. **Create the bootstrap administrator.** Provide a display name, login id, and password.
   - Login id constraints: 8–20 chars, `[0-9a-zA-Z_-]`.
   - Password constraints: 8–20 chars, alphanumeric only.
   - The password is stored using `password_hash(PASSWORD_ARGON2ID)` (M1).
5. **Lock the installer.** A successful run writes `installer/installer.json`; subsequent visits to `/installer/*` redirect to the login page.

## After installation

- Visit `/auth/login` and sign in with the bootstrap admin.
- Configure additional members from the admin panel (planned UI lands in M4 — until then, manage `Member` rows via Adminer or the SQL shell).
- Apply security recommendations from the [Security policy](../security.md):
  - Set `APP_HTTPS=true` in `.env` once TLS is provisioned at the web-server layer.
  - Restrict file permissions: `.env` and `config.json` to `0640`.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| `No such file or directory` on the DSN test | Wrong host / unix socket path. For Docker compose use `host=db`. |
| `Access denied for user` | Grants missing on the DB. Run `GRANT ALL ON saso_db.* TO 'saso_user'@'%';`. |
| Installer redirects straight to the login page | `installer/installer.json` already exists; delete it to re-run. |
| 500 Internal Server Error before any installer page renders | `.htaccess` not honored. Ensure `AllowOverride All` (Docker dev image already does this; on shared hosting check the host docs). |
| `Argon2id digest truncated` after upgrade from pre-M1 | Run [`migrations/M1_001_widen_password_column.sql`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/migrations/M1_001_widen_password_column.sql). |

## Next

- [Configuration](configuration.md) — environment variables and runtime tuning.
- [Architecture overview](../architecture/index.md) — how the codebase is organized.
