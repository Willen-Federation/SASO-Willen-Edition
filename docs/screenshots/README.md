# Baseline Screenshots / ベースラインスクリーンショット

This directory holds reference screenshots used as a **regression baseline** during the modernization work (milestones M0–M5). When refactoring legacy code under [`legacy/`](../../legacy) or replacing PHP templates with Clean-Architecture equivalents under [`src/Presentation/`](../../src/Presentation), reviewers compare the new UI to these screenshots to confirm no visual or behavioral regression.

このディレクトリは、近代化作業（マイルストーン M0〜M5）中の **リグレッション基準** として使うリファレンススクリーンショットを保管します。`legacy/` 配下のコードをリファクタリングしたり、PHP テンプレートを `src/Presentation/` 以下の Clean Architecture 実装に置換したりする際、レビュアーは新 UI を本ディレクトリのスクリーンショットと比較してリグレッションがないことを確認します。

## What to capture / 撮影対象

Take screenshots of every primary screen accessible via the existing web UI. At minimum:

| 画面 / Screen | URL pattern | Notes |
|---|---|---|
| ログイン / Login | `/auth/login` | Empty state + error state |
| インストーラ / Installer | `/installer/start` | All wizard steps (capture before completing) |
| 商品一覧 / Item list | `/item/list` | Empty + populated |
| 商品登録 / Item add | `/item/add` | Empty form |
| 商品編集 / Item edit | `/item/edit/{id}` | Existing item |
| 分類管理 / Category | `/category/edit` | Hierarchical tree visible |
| ラベル一覧 / Label list | `/label/list` | |
| ラベル PDF / Label PDF | `/label/pdf/{id}` | PDF rendering output (export PDF too) |
| 棚一覧 / Shelf list | `/shelf/list` | |
| 棚 PDF / Shelf PDF | `/shelf/pdf/{id}` | PDF rendering output |
| バーコード / Barcode | `/barcode/start` | |
| 画像登録 / Image upload | `/image/start` | |

## Conventions / 命名規則

- Filename: `<area>-<screen>-<state>.png` (e.g. `item-list-populated.png`, `auth-login-error.png`)
- Resolution: 1440 × 900 (desktop reference) — capture at 1× DPR (no Retina scaling)
- Browser: latest Chrome / Firefox stable, default zoom 100%
- Locale: Japanese (`?lang=ja`) and English (`?lang=en`) once i18n ships in M3 — both versions go in the same directory with `-ja` / `-en` suffixes

## Capture timing / 取得タイミング

| Milestone | When to capture |
|---|---|
| **M0** | After standing up a local instance (Docker, post-M2). Establish the first baseline. |
| **M1** | After security hotfix merges — verify only login/CSRF/upload flows visibly changed. |
| **M3** | After REST + i18n + error code rollout — re-capture with EN/JA pairs. |
| **M4** | After Clean Architecture migration — must be visually identical to M3 baseline. |
| **M5** | Final reference set published to MkDocs alongside release notes. |

## Tooling / ツール

Manual capture is fine for now. Once Docker + Playwright lands in M5, this directory will be re-generated automatically by `tests/e2e/screenshots.spec.ts` running in CI.

現時点では手動撮影で構いません。M5 で Docker + Playwright が導入された後、`tests/e2e/screenshots.spec.ts` が CI 内で自動再生成します。

## Status / 現状

- [ ] M0 baseline captured *(blocked: requires Docker setup from M2 — placeholder only)*
- [ ] M1 verification captures
- [ ] M3 verification captures
- [ ] M4 verification captures
- [ ] M5 release captures
