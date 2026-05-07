# Configuration

Configuration is layered. Lower layers act as defaults; higher layers override.

| Layer | What goes here | Status |
|---|---|---|
| **`.env`** | Secrets (`DB_PASSWORD`, future `OIDC_CLIENT_SECRET`, `APP_KEY`) and per-environment toggles (`APP_HTTPS`). Git-ignored. | shipped (M1) |
| **`config.json`** | Non-secret operational defaults: paths, log location, sheet count. | shipped |
| **`system_setting` table** | Runtime configuration editable from the admin Web UI. Sensitive values encrypted at rest with AES-256-GCM. | planned (M4) |

Resolution order for an overlay-able key (highest first): **`.env`** → real OS environment variable → `config.json`.

## Overlay-able keys (M1-C)

The deliberately-narrow allow-list:

| `.env` key | `config.json` target | Type |
|---|---|---|
| `DB_DSN` | `database.dsn` | string |
| `DB_USER` | `database.user` | string |
| `DB_PASSWORD` | `database.password` | string |
| `APP_HTTPS` | `https` | boolean (`filter_var FILTER_VALIDATE_BOOLEAN`) |

A leaked `.env` cannot silently change application paths or feature toggles — those stay in `config.json` (and, after M4, in the DB-backed `system_setting` table).

## Non-secret settings (`config.json`)

| Key | Description |
|---|---|
| `documentRoot` | Absolute filesystem path to the SASO directory (used by the legacy ClassLoader). |
| `programDir` | Sub-directory of DocumentRoot where SASO lives. Empty for root deployments. |
| `outputRow` | Items per page in list views (1–99). |
| `https` | Boolean toggle for in-app HTTPS redirect + HSTS. Overridden by `APP_HTTPS`. |
| `sheetAmount` | Maximum label sheets per print job (1–9999). |
| `logPath` | Directory for application logs (default `/tmp/log/`). |
| `csrftokensalt` | **Deprecated** since M1-B; ignored at runtime. Will be removed in M4. |

## Examples

### Local development (Docker compose)

`.env`:

```env
DB_DSN=mysql:host=db;dbname=saso_db;charset=utf8mb4
DB_USER=saso_user
DB_PASSWORD=saso_dev_password
APP_HTTPS=false
```

(`docker-compose.yml` already exports these values; overriding via `.env` is optional unless you change defaults.)

### Production behind a reverse proxy

`.env`:

```env
DB_DSN=mysql:host=db.internal;dbname=saso_prod;charset=utf8mb4
DB_USER=saso_app
DB_PASSWORD=<rotated quarterly>
APP_HTTPS=true
```

The HTTPS check honors `X-Forwarded-Proto`, so a proxy terminating TLS is supported automatically.

## Next

- [Security](../security.md) — operator hardening checklist.
- [Architecture](../architecture/index.md) — where each setting is consumed.
