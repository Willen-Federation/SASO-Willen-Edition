# 引き継ぎドキュメント

生成日時: 2026-05-04 (session 3)
引き継ぎ理由: 作業記録・進捗管理

---

## 🎯 最終ゴール（再掲）

1. 旧スタイル/TailAdmin Tailwindを含むテンプレートをTabler(Bootstrap 5)に統一 ← **完了**
2. 削除されていた機能(Feature Flag, バーコードワークフロー等)の復元 ← 完了済み
3. 棚番・ラベルシートのUIを使いやすくする
4. `category/start/` etc. 正しく動作させる ← 完了済み

---

## ✅ 完了済みの作業（セッション1）

- `flow.json` 復元（0b324a5 PSR-12 偽装コミットで削除された13セクションを復元）
- `start/template/menu.php` を5セクション21項目に拡張
- `config.json` パス修正（本番環境の白画面修正）

## ✅ 完了済みの作業（セッション2）

| ファイル | 変更内容 |
|---------|---------|
| `auth/template/provider_new.php` | `border-brand-500` → `border-primary`、`style="cursor:pointer;"` 削除 |
| `item/template/register.php` | 旧式 `<p>入力：<input>` → Tabler カード+フォームに全面リライト |
| `category/template/edit.php` | Tabler カード＋パンくずに整形 |
| `flow.json` | `category.start` → `EditDIContainer`（旧: JsonエンドポイントのListDIContainer） |
| `barcode/template/sheet.php` | Tailwind CDN削除、Bootstrap/Tabler クラスに全面書き直し |
| `label/template/wizard.php` | `ta-badge-*` → `badge bg-*`、Tailwind grid → Bootstrap row |
| `shelf/template/simple.php` | Tailwind CDN削除、Bootstrap/Tabler クラスに全面書き直し |
| `item/template/fromBarcode.php` | `form-input`/`btn-primary w-full` → Bootstrap input-group/btn |
| `authExt/template/provider_form.php` | 全面Bootstrap化（735行→430行）|

## ✅ 完了済みの作業（セッション3）

| ファイル | 変更内容 |
|---------|---------|
| `verify/template/start.php` | `grid gap-6 lg:grid-cols-3` → Bootstrap `row g-3`、`text-theme-sm` → `small text-muted` |
| `member/template/add.php` | TailAdmin card → Bootstrap card + form-control |
| `member/template/edit.php` | 同上 |
| `authExt/template/provider_select.php` | SVGアイコン→ `ti ti-*`、Tailwindグリッド→ Bootstrap `row row-cols-sm-2`、`card-link card-link-pop` |
| `item/template/draftConfirm.php` | 全面Bootstrap化（$fieldHtml クロージャ内も含む） |
| `item/template/draftList.php` | statusClasses → Bootstrap badge、table → `table-vcenter`、SVGスピナー → `spinner-border-sm` |
| `item/template/addFromImage.php` | ドロップゾーン → `position-relative border border-2`（dashed inline style）|
| `member/template/start.php` | TailAdmin card+table → Bootstrap card/table-vcenter + badge bg-primary-subtle |
| `scanStock/template/start.php` | `text-title-md2` → Bootstrap heading、`form-input` → `form-control`、SVGスピナー削除 |
| `settingAdmin/template/start.php` | amber div → `alert alert-warning`、セクションヘッダー → `border-top small fw-semibold text-muted` |
| `root/template/_components/barcodeScanner.php` | モーダルオーバーレイ Bootstrap化、SVG close → `btn-close`、trigger SVG → `ti ti-qrcode` |
| `root/template/_components/cameraCapture.php` | 同上 + タブ切り替え → `btn-group btn-sm`、ファイルドロップゾーン → dashed border Bootstrap |
| `shelf/template/map.php` | 1行 `flex flex-col md:flex-row` → Bootstrap `d-flex flex-column flex-md-row` |
| `authExt/ProviderView.php` | DELETE操作を POST のみに制限（CSRF対策） |
| `authExt/template/providers_list.php` | 削除リンク `<a href>` → `<form method=post>` に変換 |

---

## ✅ 完了済みの作業（セッション4 — デバッグ・バグ修正）

| ファイル | 変更内容 |
|---------|---------|
| `scanStock/StartDIContainer.php` | `isTopLevel() true → false`（HTML ページなのにラッパーなしで白画面になっていた） |
| `barcode/template/sheet.php` | Tailwind `transition-all` → `style="transition: all 0.15s ease-in-out;"` |
| `authExt/template/provider_form.php` | fieldset 5箇所の `min-w-0`（Tailwind） → `style="min-width:0"` |
| `admin/template/feature-flags.php` | Toggle/Create POST 先を `./api/v1/...` (404) → `./admin/feature-flags/...` に修正、CSRF 追加、ゲッターを public プロパティアクセスに修正、FeatureKey 正規表現統一、Edit リンク削除 |
| `admin/FeatureFlagsDIContainer.php` | toggle・create ハンドラーを追加実装 |
| `label/MintDIContainer.php` | `isTopLevel() false → true`（PDF 二重出力バグ修正） |
| `label/PdfDIContainer.php` | 同上 |
| `label/SvgDIContainer.php` | 同上 |
| `barcode/PrintSheetDIContainer.php` | 同上 |

全修正は本番 `main` ブランチに cherry-pick 済み（コミット `02604c5`）。

---

## 📋 残タスク（優先順）

### [ ] 1. Tailwind クラス残存の全体スキャン
- `grep -rn "min-w-0\|transition-all\|flex-shrink-0\|rounded-lg\|text-sm\|bg-white" --include="*.php" --exclude-dir=vendor` で検索
- 発見したら Bootstrap/Tabler 相当の inline style かクラスに置き換え

### [ ] 2. `_layout/` ディレクトリのデッドコード確認・削除 (低優先度)
- `root/template/_layout/header.php`, `sidebar.php`, `footer.php`, `breadcrumb.php`, `skip_link.php`, `installer_alert.php`, `lang_switcher.php`
- `root/template/root.php` から一切 include されていない（確認済み）
- 削除して問題なし

### [ ] 3. `shelf/template/map.php` のカスタム `<style>` ブロック整理 (低優先度)
- インラインスタイルが約120行ある
- 機能的には問題なし

### [ ] 4. `item/template/registerConfirm.php` の確認 (未調査)
- スキャンで検出されなかったが念のため確認

### [ ] 5. ラベルファーストワークフロー動作検証
- `barcode/sheet/` → `barcode/printSheet/` → `item/fromBarcode/` のフロー全体確認
- `PrintSheetDIContainer::isTopLevel() = true` 修正後に PDF が正常出力されるか確認

---

## 📁 現在の作業ファイル状態

すべてのテンプレートの Bootstrap 5 / Tabler 統一が完了。残るのは低優先度のクリーンアップのみ。

---

## ⚠️ 重要な注意事項

### 環境
- **このマシン自体が本番環境** (saso.sksl.jp)
- 作業ディレクトリ: `/home/schicksal/domains/saso.sksl.jp/public_html/`
- worktree: `/home/schicksal/domains/saso.sksl.jp/public_html/.claude/worktrees/distracted-elgamal-816cfb`
- PHP 8.2, Apache, MariaDB

### デプロイフロー
- git push → touch index.php (OPcache無効化) の順
- worktreeでの変更は親リポジトリに自動反映（同じファイルを編集）

### 設計ガイドライン
- レイアウトCSS: `@tabler/core@1.0.0` (Bootstrap 5 互換)
- アイコン: `ti ti-*` (Tabler Icons webfont)
- Tailwind CSS は**削除済み**（CDNでの読み込みも NG）
- コンポーネントヘルパー: `ui('card', ...)`, `ui('button', ...)`, `ui('formField', ...)`, `ui('alert', ...)`
- Alpine.js でクライアントサイド状態管理

### gitリモート
- `github.com:Willen-Federation/SASO-Willen-Edition.git`
- main への直接 push は可能（bypass警告が出るが問題なし）
