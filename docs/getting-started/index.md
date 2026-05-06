# Getting Started

Three supported paths for standing up a SASO instance:

1. [**Docker**](#docker-colima-or-docker-desktop) — recommended for development and evaluation. The dev stack mirrors a typical mod_php + Apache + MariaDB shared-hosting environment.
2. [**Standard PHP host**](#standard-php-host) — a Linux box you administer (VPS, dedicated, or a managed PaaS that lets you run PHP 8.2+).
3. [**Shared / rental hosting**](#shared-or-rental-hosting) — Apache + .htaccess hosts where SSH and Composer are not available. Use the release ZIP (planned for M5) which bundles `vendor/`.

After installation the application schema is created interactively via `installer/start`. See [Installation](installation.md) for the full walkthrough.

## Requirements

| Component | Version |
|---|---|
| PHP | 8.2+ |
| MariaDB / MySQL | 10.6+ / 8.0+ |
| Web server | Apache 2.4+ with `mod_rewrite` (LiteSpeed compatible) |
| Required PHP extensions | `pdo_mysql`, `gd`, `zip`, `intl`, `mbstring`, `opcache`, `fileinfo` |

## Docker (Colima or Docker Desktop)

```bash
colima start --cpu 4 --memory 4 --disk 20   # macOS only — skip on Linux
make up                                      # build images + start app, db, adminer
make install                                 # composer install in the app container
make migrate                                 # apply migrations/*.sql
open http://localhost:8080/installer/start
```

Adminer is at `http://localhost:8081` (server `db`, user `saso_user`, password: see `DB_PASSWORD` in the auto-generated `.env`).

For local OIDC / SAML testing add the `sso` profile:

```bash
make up-sso       # Keycloak at http://localhost:8082 (admin / admin)
```

## Standard PHP host

```bash
git clone https://github.com/Willen-Federation/SASO-Willen-Edition.git
cd SASO-Willen-Edition
composer install --no-dev --optimize-autoloader

cp .env.example .env
$EDITOR .env             # set DB_DSN / DB_USER / DB_PASSWORD / APP_HTTPS

$EDITOR .htaccess        # adjust RewriteBase if installed in a sub-directory

# Then visit /installer/start in your browser.
```

## Shared or rental hosting

If your host does not provide SSH or Composer:

1. Download the latest release ZIP from [GitHub Releases](https://github.com/Willen-Federation/SASO-Willen-Edition/releases) — `vendor/` is pre-bundled.
2. Upload the contents of the ZIP into the chosen install directory via cPanel / FTP.
3. Edit `.htaccess` and set `RewriteBase` to match the install path.
4. Visit `<your-domain>/installer/start` and complete the wizard.
5. The wizard writes `installer/installer.json` to lock subsequent visits.

> Release ZIPs ship in **M5**. Until then, build one yourself by running `composer install --no-dev` on a development machine and zipping the result.

## Next

- [Installation walkthrough](installation.md)
- [Configuration reference](configuration.md)
- [Security policy](../security.md)
