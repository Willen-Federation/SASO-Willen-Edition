# SASO — Willen Edition

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](#requirements)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.6%2B-003545)](#requirements)
[![Status](https://img.shields.io/badge/status-modernizing-orange)](#roadmap)
[![Powered by Netlify](https://img.shields.io/badge/powered%20by-Netlify-00C7B7?logo=netlify&logoColor=white)](https://www.netlify.com)

**SASO — Willen Edition** is an open-source inventory and warehouse management system written in PHP. It supports item / category management, barcode display, label printing (PDF), and shelf management. This edition is a community-maintained fork of the original [SASO](https://hyoujun.jp/) by Japan Standards Organization (日本標準機構), modernizing the codebase for global use.

[日本語版はこちら](#日本語) / [Original Japanese README](ORIGINAL_README.md)

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Quick Start (Docker)](#quick-start-docker)
- [Quick Start (Standard PHP, no Docker)](#quick-start-standard-php-no-docker)
- [Configuration](#configuration)
- [Roadmap](#roadmap)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)
- [Acknowledgements](#acknowledgements)

---

## Features

- 📦 **Inventory management** — items, categories (nested set model), images
- 🏷️ **Label printing** — PDF generation via TCPDF with customizable layouts
- 📊 **Barcode display** — QR Code, Data Matrix, PDF417 (via TCPDF barcode engine)
- 🗂️ **Shelf management** — single / multi-shelf views, PDF export
- 🔐 **Web-based installer** — no SSH required, runs on standard shared hosting
- 🌐 **Multi-language ready** — i18n infrastructure being added (see [Roadmap](#roadmap))

## Requirements

| Component | Version |
|---|---|
| PHP | 8.2+ |
| MariaDB / MySQL | 10.6+ / 8.0+ |
| Web server | Apache 2.4+ with `mod_rewrite` (LiteSpeed compatible) |
| PHP extensions | `pdo_mysql`, `gd`, `zip`, `intl`, `mbstring`, `opcache` |

## Quick Start (Docker)

Zero-touch — `make up` generates a `.env` with secure random secrets
(`APP_KEY`, `DB_PASSWORD`, `MARIADB_ROOT_PASSWORD`) on first run.

```bash
git clone https://github.com/Willen-Federation/SASO-Willen-Edition.git
cd SASO-Willen-Edition

make up                          # generates .env, builds images, starts app+db+adminer
make install                     # composer install inside the app container
make migrate                     # apply Phinx migrations

open http://localhost:8080/installer/start    # create the first admin user
```

That's it. AI provider keys (Gemini / OpenAI / Anthropic) and Auth0
credentials are configured later from the admin Web UI under **Settings →
AI** and **Settings → Auth Providers** — they're stored encrypted in the
`system_setting` DB table, not in `.env`.

For a developer override (e.g. testing an AI provider before logging in
once), the same env vars are still honoured: open `.env` and fill in the
`Optional developer overrides` block.

For development with an OIDC / SAML test IdP:

```bash
make up-sso                      # adds Keycloak at http://localhost:8082 (admin / admin)
```

> **Adminer** at <http://localhost:8081> uses the credentials from the
> generated `.env` (server: `db`, user: `saso_user`, password: see
> `DB_PASSWORD` in `.env`).

Tested with **Colima** and **Docker Desktop** on macOS / Linux. Recommended
Colima sizing on macOS: `colima start --cpu 4 --memory 4 --disk 20`. Apple
Silicon hosts run the images under qemu (`platform: linux/amd64`) so the
developer environment matches the deployment surface most operators target.

## Quick Start (Standard PHP, no Docker)

```bash
# 1) Clone
git clone https://github.com/Willen-Federation/SASO-Willen-Edition.git
cd SASO-Willen-Edition

# 2) (Optional) Install Composer dependencies — required for the PSR-4
#    autoloader at src/ and for the dev tooling (PHPUnit, PHPStan).
#    Skippable on shared hosts that do not have Composer; release ZIPs
#    bundle vendor/ pre-installed.
composer install --no-dev --optimize-autoloader

# 3) Create the database (MariaDB / MySQL)
mysql -u root -p -e "CREATE DATABASE saso CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4) Configure DB credentials — preferred: .env (kept out of git)
cp .env.example .env
$EDITOR .env             # set DB_DSN / DB_USER / DB_PASSWORD

# 5) Edit .htaccess — set RewriteBase to your install directory
$EDITOR .htaccess

# 6) Open the web installer in your browser
open https://your-host.example.com/installer/start
```

`APP_KEY` is generated automatically when you first open
`/installer/start` (the app writes it back into your `.env`). After that,
follow the on-screen wizard to create the initial administrator account.

> **Shared / rental hosting**: For environments without SSH or Composer access, download the latest release ZIP from [Releases](https://github.com/Willen-Federation/SASO-Willen-Edition/releases) (vendor dependencies pre-bundled), upload via cPanel / FTP, and proceed from step 4.

For development with an OIDC / SAML test IdP:

```bash
make up-sso              # adds Keycloak at http://localhost:8082 (admin / admin)
```

`make help` lists every target. Common ones:

| Target | What it does |
|---|---|
| `make up` / `make down` | Start / stop the stack |
| `make shell` | Open a bash shell in the `app` container |
| `make test` / `make analyse` / `make cs-check` | Run PHPUnit / PHPStan / PHP-CS-Fixer |
| `make qa` | Run all three QA tools in sequence |
| `make migrate` | Apply each `migrations/*.sql` against the dev DB |
| `make db-shell` | Open a MariaDB client connected to `saso_db` |

## Configuration

Configuration is layered. Lower layers act as defaults; higher layers override.

| Layer | What goes here | Status |
|---|---|---|
| **`.env`** | Secrets (`DB_PASSWORD`, `APP_KEY`, `MARIADB_ROOT_PASSWORD`) and per-environment toggles (`APP_HTTPS`). Auto-generated on first `make up`. Git-ignored. | shipped (M1) |
| **`config.json`** | Non-secret operational defaults: paths, log location, sheet count. Written by the installer; commitable as a template. | shipped |
| **`system_setting` table** | Runtime configuration editable from the admin Web UI — including AI provider keys (`ai.gemini_api_keys`, `ai.openai_api_keys`, `ai.anthropic_api_keys`) and Auth0 (`auth0.*`). Sensitive values encrypted at rest with AES-256-GCM. | shipped (M4) |

Resolution order for an overlay-able key (highest first): `system_setting` (DB) → `.env` → real OS environment variable → `config.json`. The `.env`-overlay-able keys are `DB_DSN`, `DB_USER`, `DB_PASSWORD`, `APP_HTTPS`, and `APP_KEY`; everything else is read from `config.json` or the `system_setting` table only.

## Roadmap

This fork modernizes SASO across six milestones. See [`/Users/akira/.claude/plans/docker-colima-oss-openapi-restful-linear-church.md`](.) (internal plan) and the [GitHub Project board](https://github.com/Willen-Federation/SASO-Willen-Edition/projects).

| Milestone | Focus |
|---|---|
| **M0 — Stabilize** | Repository hygiene, CI lint, baseline screenshots |
| **M1 — Security Hotfix** | Argon2id passwords, request-bound CSRF, `.env` isolation, HTTPS enforcement, upload validation |
| **M2 — Tooling & Composer** | `composer.json`, Docker (Colima-compatible), PHPUnit, PHPStan, MkDocs scaffold |
| **M3 — REST + i18n + Errors** | OpenAPI 3.1 at `/api/v1/*`, `symfony/translation`, RFC 7807 problem details, OIDC/SAML scaffold |
| **M4 — DDD + Feature Flag + Web Settings** | Clean Architecture layout, OpenFeature with circuit breaker, pluggable IdP admin UI |
| **M5 — Hardening & Release** | Vendor-bundled release ZIPs, Web installer rewrite, E2E tests, runbooks |

## Documentation

📚 **[https://willen-federation.github.io/SASO-Willen-Edition/](https://willen-federation.github.io/SASO-Willen-Edition/)** — full developer documentation site (English / 日本語) built with Material for MkDocs.

Local preview:

```bash
pip install -r requirements.txt
mkdocs serve         # → http://localhost:8000
```

Repository-level references:

- [Original Japanese README](ORIGINAL_README.md)
- [Contributing guide](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](CHANGELOG.md)

## Contributing

We welcome contributions from anywhere in the world. English and Japanese are both accepted in issues and pull requests. See [CONTRIBUTING.md](CONTRIBUTING.md) for development workflow, code style (PSR-12), and testing.

## Security

If you discover a vulnerability, please **do not** open a public issue. See [SECURITY.md](SECURITY.md) for the responsible disclosure process.

## License

Code authored by Japan Standards Organization (日本標準機構) and Willen Federation contributors is licensed under the [GNU GPL v3](LICENSE). Bundled third-party libraries under `extention/` retain their original licenses (e.g. TCPDF: LGPLv3).

## Acknowledgements

- **Original author**: 日本標準機構 (Japan Standards Organization) — saso(at)hyoujun.jp
- **Bundled libraries**: [TCPDF](https://tcpdf.org/) by Nicola Asuni
- **Maintainer of this edition**: [Willen Federation](https://github.com/Willen-Federation)

---

## 日本語

**SASO — Willen Edition** は、PHP で書かれたオープンソースの在庫・倉庫管理システムです。商品・分類管理、バーコード表示、ラベル印刷（PDF）、棚管理に対応しています。本エディションは [日本標準機構](https://hyoujun.jp/) によるオリジナル SASO のコミュニティメンテナンス版で、グローバル利用に向けてコードベースを近代化しています。

### 動作要件

- PHP 8.2 以上
- MariaDB 10.6 以上 / MySQL 8.0 以上
- Apache 2.4 以上（`mod_rewrite` 必須、LiteSpeed 互換可）
- PHP 拡張：`pdo_mysql` / `gd` / `zip` / `intl` / `mbstring` / `opcache`

### インストール手順

詳細は [ORIGINAL_README.md](ORIGINAL_README.md) または上記の [Quick Start](#quick-start-standard-php) を参照してください。要約：

1. **Docker** の場合：`make up` で `.env` が自動生成され、すぐにスタックが起動します（推奨）。
2. **手動セットアップ** の場合：データベースを作成し、`.env` をコピーして DB 接続情報を設定、`.htaccess` の `RewriteBase` を設定。
3. ブラウザから `installer/start` にアクセスして初回セットアップ（`APP_KEY` は自動生成されます）。
4. AI プロバイダや Auth0 の API キーは、ログイン後に管理画面（**Settings → AI / Auth Providers**）から登録します。

### 貢献方法

[CONTRIBUTING.md](CONTRIBUTING.md) を参照してください。Issue・Pull Request は日本語・英語どちらでも受け付けます。

### ライセンス

GNU GPL v3。詳細は [LICENSE](LICENSE) を参照。
