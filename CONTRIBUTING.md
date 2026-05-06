# Contributing to SASO — Willen Edition

Thank you for considering a contribution! This project welcomes pull requests, issues, translations, and feedback from anywhere in the world. **English and Japanese are both first-class languages** for issues, PRs, and discussions.

> 日本語版は[こちら](#日本語版)。

---

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Ways to Contribute](#ways-to-contribute)
- [Development Setup](#development-setup)
- [Branching & Workflow](#branching--workflow)
- [Commit Message Style](#commit-message-style)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Checklist](#pull-request-checklist)
- [Translation Contributions (i18n)](#translation-contributions-i18n)
- [Reporting Security Issues](#reporting-security-issues)
- [日本語版](#日本語版)

---

## Code of Conduct

This project follows the [Contributor Covenant v2.1](CODE_OF_CONDUCT.md). By participating, you agree to uphold its standards. Report unacceptable behavior privately to the maintainers (see SECURITY.md for the reporting channel).

## Ways to Contribute

- 🐛 **Report bugs** — open an issue using the bug report template
- 💡 **Suggest features** — open an issue using the feature request template
- 🌐 **Translate the UI / docs** — see [Translation Contributions](#translation-contributions-i18n)
- 📝 **Improve documentation** — typos, clarifications, or additional examples
- 🔧 **Submit code** — fix a bug, implement a feature, or refactor
- 🔒 **Disclose vulnerabilities** — see [Reporting Security Issues](#reporting-security-issues) (do **not** open a public issue)

## Development Setup

### Docker (Colima / Docker Desktop) — recommended

```bash
git clone https://github.com/Willen-Federation/SASO-Willen-Edition.git
cd SASO-Willen-Edition

make up           # generates .env (APP_KEY/DB_PASSWORD/MARIADB_ROOT_PASSWORD), builds images, starts stack
make install      # composer install inside the app container
make migrate      # apply migrations against saso_db
make qa           # cs-check + analyse + test

open http://localhost:8080/installer/start
```

`.env` is created automatically with secure random secrets on first
`make up`. Re-running `make up` is a no-op for `.env`. AI provider and
Auth0 credentials are configured later from the admin Web UI (**Settings →
AI / Auth Providers**) — they're stored encrypted in the `system_setting`
table, not in `.env`.

### Standard PHP environment (no Docker)

```bash
git clone https://github.com/Willen-Federation/SASO-Willen-Edition.git
cd SASO-Willen-Edition

cp .env.example .env
$EDITOR .env             # set DB_DSN / DB_USER / DB_PASSWORD

# Set RewriteBase to your install path
$EDITOR .htaccess

# Open the installer in a browser — APP_KEY is auto-generated on first hit
open https://your-host.example.com/installer/start
```

For local OIDC / SAML testing with Keycloak (optional profile):

```bash
make up-sso       # same as `make up` plus Keycloak on :8082
```

`make help` lists every target. The `app` container bind-mounts the
repository at `/var/www/html`, so file edits are visible immediately
without rebuilding the image.

## Branching & Workflow

We use [**GitHub Flow**](https://docs.github.com/en/get-started/using-github/github-flow):

1. Fork the repository (external contributors) or create a branch (maintainers).
2. Branch from `main` with a descriptive name: `feat/openapi-routes`, `fix/upload-mime-validation`, `docs/contributing-en`.
3. Open a Pull Request early as a **draft** to share work-in-progress.
4. Push commits; CI must pass before review.
5. Request review from `@Willen-Federation/maintainers`.
6. Squash-merge to `main` after approval.

Branch protection on `main` requires:

- ✅ Passing CI (`php -l`, PHPStan, PHPUnit, OpenAPI diff — added progressively)
- ✅ At least one approving review
- ✅ Up-to-date with `main`

## Commit Message Style

We follow [**Conventional Commits 1.0**](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

| Type | Use |
|---|---|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Formatting, no logic change |
| `refactor` | Restructuring without behavior change |
| `perf` | Performance improvement |
| `test` | Adding or fixing tests |
| `chore` | Tooling, build, dependencies |
| `ci` | CI/CD changes |
| `security` | Security-related fix |

Example:

```
feat(auth): add OIDC login with PKCE via jumbojett

- Pluggable AuthProvider interface
- Stores external_subject on Member for SSO provisioning
- Documented IdP setup in docs/auth-providers/

Closes #42
```

## Coding Standards

- **PHP**: PSR-12 (enforced by `php-cs-fixer`, configured in `.php-cs-fixer.dist.php`)
- **Static analysis**: PHPStan level 6 minimum (raising over time)
- **Naming**: PascalCase for classes, camelCase for methods/variables, UPPER_SNAKE for constants
- **Strict types**: `declare(strict_types=1);` at the top of every new PHP file
- **Comments**: prefer self-explanatory code; comment only the *why* (constraints, invariants, workarounds)
- **No globals**: use dependency injection via the existing DI container
- **Errors**: throw typed exceptions; do not `die()` or `exit()` outside framework boundaries
- **i18n**: never hardcode user-visible strings; use the `__('translation.key')` helper (introduced in M3)

### Setting up dev tooling

```bash
# Install all PHP dependencies (including PHPUnit, PHPStan, PHP-CS-Fixer)
composer install
```

Run formatters and analyzers before pushing:

```bash
composer cs:fix      # auto-fix PSR-12 violations (php-cs-fixer)
composer analyse     # static analysis (phpstan)
composer test        # unit tests (phpunit)
composer cs:check    # check style without modifying (phpunit + diff output)
```

**Configuration**:

| File | Tool | Scope today |
|---|---|---|
| `phpunit.xml.dist` | PHPUnit 10.5 | `tests/Unit`, `tests/Integration`, `tests/Feature` |
| `phpstan.neon.dist` | PHPStan **level 6** | `src/`, `tests/`, three M1 utility rewrites in `util/` |
| `.php-cs-fixer.dist.php` | PHP-CS-Fixer | `src/`, `tests/`, three M1 utilities (PSR-12 + short arrays + ordered imports + single quotes + trailing commas) |

Legacy directories (`auth/`, `item/`, `framework/`, `repository/`, …) are
deliberately excluded from PHPStan and PHP-CS-Fixer until they migrate to
`src/` across M3-M4. Including them today would bury actionable signal under
hundreds of pre-existing magic-property and mixed-type errors.

> The same commands run in CI (`cs-check`, `static-analysis`, `unit-tests`
> jobs). Push only after they all pass locally.

## Testing

- Unit tests live in `tests/Unit/`, integration in `tests/Integration/`, feature in `tests/Feature/`.
- Coverage targets: Domain ≥ 80%, Application ≥ 60%, overall ≥ 50%.
- Integration tests **must** run against a real MariaDB (mocking the DB has historically masked migration bugs — see ADR 0002 once added).
- E2E (Playwright in Docker) is added in M5.

```bash
# Run the full test suite
composer test

# Run a single suite
vendor/bin/phpunit --testsuite=Unit
```

## Pull Request Checklist

Before requesting review:

- [ ] CI passes (lint, PHPStan, PHPUnit)
- [ ] New code has tests (unit / integration as appropriate)
- [ ] Public APIs are documented (PHPDoc + OpenAPI annotations if HTTP-exposed)
- [ ] User-visible strings use the i18n helper
- [ ] `CHANGELOG.md` updated under `## [Unreleased]`
- [ ] No committed secrets, `.env` files, or generated `vendor/` directories
- [ ] PR description explains the *why*, not just the *what*

## Translation Contributions (i18n)

Translation infrastructure ships in M3 (`symfony/translation` + YAML).

- Translations live in `translations/<lang>.yaml`.
- The repository ships with `ja.yaml` (default) and `en.yaml` (required).
- To add a new language (e.g. Spanish):
  1. Copy `translations/en.yaml` to `translations/es.yaml`.
  2. Translate values, keeping keys identical.
  3. Add the language to the language switcher in `config/locales.php`.
  4. Open a PR — tag it with the `i18n` label.
- Documentation translations follow the same pattern under `docs/<lang>/` with `mkdocs-static-i18n`.
- For PDF label output, additional fonts (e.g. Noto Sans) may be required; coordinate via an issue first.

## Reporting Security Issues

Please **do not** open a public issue for vulnerabilities. See [SECURITY.md](SECURITY.md) for the responsible disclosure process and our PGP / contact details.

---

## 日本語版

SASO — Willen Edition への貢献を検討いただきありがとうございます。Pull Request、Issue、翻訳、フィードバックを世界中から歓迎しています。**Issue・PR は日本語と英語のどちらでも構いません。**

### 行動規範

[Contributor Covenant v2.1](CODE_OF_CONDUCT.md) に従います。参加することで、その内容を遵守することに同意したものとみなされます。

### 貢献の方法

- 🐛 バグ報告 — Issue テンプレート（バグ報告）を使用
- 💡 機能提案 — Issue テンプレート（機能要望）を使用
- 🌐 UI / ドキュメントの翻訳 — 詳細は上記 [Translation Contributions](#translation-contributions-i18n) 参照
- 📝 ドキュメント改善 — 誤字修正、説明の追加、例の追加
- 🔧 コード貢献 — バグ修正、機能実装、リファクタリング
- 🔒 脆弱性報告 — [SECURITY.md](SECURITY.md) を参照（公開 Issue を立てないでください）

### 開発環境セットアップ

英語版の [Development Setup](#development-setup) を参照してください。

### ブランチ戦略

GitHub Flow を採用しています：

1. `main` から短命の feature ブランチを作成（`feat/...`, `fix/...`, `docs/...`）
2. 早めに draft Pull Request を開き、進行状況を共有
3. CI を通過させ、レビュー依頼
4. 承認後 squash-merge

### コミットメッセージ

[Conventional Commits 1.0](https://www.conventionalcommits.org/ja/) に従います。詳細は英語版の [Commit Message Style](#commit-message-style) を参照。

### コード規約

- PHP: PSR-12（`php-cs-fixer` 自動整形）
- 静的解析: PHPStan level 6 以上
- 新規 PHP ファイル冒頭に `declare(strict_types=1);`
- ユーザー可視文字列はハードコード禁止、`__('translation.key')` ヘルパを使用（M3 で導入）
- コメントは「なぜ」だけ書く（「何を」はコードで表現）

### テスト

- 単体: `tests/Unit/`、統合: `tests/Integration/`、E2E: `tests/Feature/`
- カバレッジ目標: Domain 80% / Application 60% / 全体 50%
- 統合テストは実 MariaDB に対して実行（モックは過去に migration バグを隠蔽した経緯あり）

### Pull Request チェックリスト

- [ ] CI 通過（lint / PHPStan / PHPUnit）
- [ ] 新規コードにテストあり
- [ ] 公開 API は PHPDoc / OpenAPI 注釈あり
- [ ] ユーザー可視文字列は i18n ヘルパ経由
- [ ] `CHANGELOG.md` の `## [Unreleased]` に追記済
- [ ] シークレット / `.env` / `vendor/` を含めていない
- [ ] PR 本文に「なぜ」を記載

### 翻訳貢献

翻訳基盤は M3（`symfony/translation` + YAML）で導入予定です。詳細は英語版を参照してください。

### セキュリティ脆弱性の報告

公開 Issue ではなく、[SECURITY.md](SECURITY.md) の手順に従ってください。
