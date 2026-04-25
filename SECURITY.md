# Security Policy

We take the security of SASO — Willen Edition seriously. This document describes how to report vulnerabilities and our supported versions.

> 日本語版は[こちら](#日本語版)。

---

## Supported Versions

| Version | Supported |
|---|---|
| `main` (development) | ✅ |
| `2.4.x` (latest stable) | ✅ |
| `< 2.4.0` | ❌ — please upgrade |

Security fixes are prioritized for the latest stable release line and `main`. Backports to older lines are evaluated case by case.

## Reporting a Vulnerability

**Please do not open a public GitHub issue, pull request, or discussion for security-related reports.**

Use one of the following private channels:

1. **GitHub Private Vulnerability Reporting** *(preferred)*
   <https://github.com/Willen-Federation/SASO-Willen-Edition/security/advisories/new>

2. **Email** the maintainers at:
   - `security@willen-federation.example` *(replace with the actual address before publishing)*

Please include:

- A clear description of the issue and its impact.
- Steps to reproduce (proof-of-concept code, payloads, environment details).
- Affected versions / commits if known.
- Your name and contact for credit (optional — anonymous reports are accepted).

## Our Commitment

- **Acknowledgement** within 3 business days of receipt.
- **Initial triage** (severity assessment, reproduction) within 7 business days.
- **Status updates** at least every 14 days until resolution.
- **Coordinated disclosure**: we agree on a public-disclosure date with the reporter; default is **90 days** from acknowledgement, or earlier if a fix is shipped.
- **Credit**: reporters are credited in the [security advisory](https://github.com/Willen-Federation/SASO-Willen-Edition/security/advisories) and `CHANGELOG.md` unless they request anonymity.

## Scope

In scope:

- The PHP application code in this repository (excluding `extention/` third-party libraries — report those upstream).
- Default configuration files shipped in this repository.
- Build / release artifacts published under [Releases](https://github.com/Willen-Federation/SASO-Willen-Edition/releases).

Out of scope:

- Self-hosted misconfigurations not derived from upstream defaults.
- Vulnerabilities in third-party dependencies — please report directly to their maintainers; we will track them via `composer audit` and our advisory feeds.
- Social engineering or physical attacks against operators.

## Hardening Recommendations

Operators should:

- **Move database credentials out of `config.json`**: copy `.env.example` to
  `.env` and set `DB_DSN`, `DB_USER`, `DB_PASSWORD` there. `.env` is
  git-ignored and overrides the matching `database.*` entries in `config.json`.
  This way a backup or accidental publication of `config.json` does not leak
  credentials.
- Provision TLS 1.2+ at the web-server level. Set `APP_HTTPS=true` in `.env`
  (or `https: true` in `config.json`) to activate the in-app HTTPS redirect,
  HSTS header, and `Secure` session cookie flag.
- Restrict file permissions: `.env` and `config.json` should be `0640`
  (owner: web user, group: deployer). `.env` should never be web-readable.
- Run behind a reverse proxy or WAF where possible. The HTTPS check honors
  `X-Forwarded-Proto`.
- Subscribe to this repository's [security advisories](https://github.com/Willen-Federation/SASO-Willen-Edition/security/advisories) for notifications.

The application itself sets `HttpOnly`, `SameSite=Lax`, and (when `https: true`)
`Secure` on the session cookie via `session_set_cookie_params()`, so manual
`php.ini` tuning is no longer required for those flags.

## PGP / Encryption *(optional)*

If you require encrypted communication, request a current PGP public key via the email address above. We will publish a key fingerprint in this file once issued.

---

## 日本語版

SASO — Willen Edition のセキュリティを真摯に受け止めています。本書では脆弱性の報告手順と対応方針を説明します。

### 対象バージョン

| バージョン | サポート |
|---|---|
| `main`（開発版） | ✅ |
| `2.4.x`（最新安定版） | ✅ |
| `2.4.0` 未満 | ❌ — アップグレードを推奨 |

### 脆弱性の報告方法

**公開 Issue・Pull Request・Discussion で報告しないでください。**

以下のいずれかの非公開チャネルを使用してください：

1. **GitHub Private Vulnerability Reporting**（推奨）
   <https://github.com/Willen-Federation/SASO-Willen-Edition/security/advisories/new>

2. **メール**：`security@willen-federation.example` *（実際の運用アドレスへ差し替え予定）*

報告には以下を含めてください：

- 問題の概要と影響範囲
- 再現手順（PoC コード、ペイロード、環境情報）
- 影響を受けるバージョン / コミット（判明している場合）
- クレジット表記の要否（匿名でも可）

### 対応方針

- 受領から 3 営業日以内に **受付確認**
- 7 営業日以内に **初期トリアージ**（重要度判定・再現確認）
- 解決まで 14 日ごとに **ステータス更新**
- **協調公開**：報告者と公開日を合意（既定は受付確認から 90 日、または修正リリース時点）
- 修正後は **クレジット表記**（匿名希望の場合は除く）

### 報告対象範囲

対象内：本リポジトリの PHP アプリケーションコード（`extention/` 以下のサードパーティを除く）、同梱デフォルト設定、Releases で配布される成果物。

対象外：上流デフォルトに起因しない自前運用の設定ミス、サードパーティ依存の脆弱性（各上流へ報告してください）、運用者へのソーシャルエンジニアリングや物理攻撃。

### 運用者向けの推奨事項

M1（セキュリティホットフィックス）完了までの間、以下を推奨します：

- Web サーバ層で HTTPS を強制（TLS 1.2 以上）
- `php.ini` で `session.cookie_httponly = 1`、`session.cookie_secure = 1`、`session.cookie_samesite = Lax`
- `config.json` のパーミッションは `0640`
- リバースプロキシ・WAF の前段配置
- 初回デプロイ時に `csrftokensalt` の値を必ず変更
- 本リポジトリの [security advisories](https://github.com/Willen-Federation/SASO-Willen-Edition/security/advisories) を購読
