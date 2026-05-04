# 引き継ぎドキュメント

生成日時: 2026-05-04 (session 2)
引き継ぎ理由: 作業記録・進捗管理

---

## 🎯 最終ゴール（再掲）

1. 旧スタイル/TailAdmin Tailwindを含むテンプレートをTabler(Bootstrap 5)に統一
2. 削除されていた機能(Feature Flag, バーコードワークフロー等)の復元 ← 完了済み
3. 棚番・ラベルシートのUIを使いやすくする
4. `category/start/` etc. 正しく動作させる

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

---

## 📋 残タスク（優先順）

### [ ] 1. `authExt/template/provider_form.php` のスタイル修正 (高優先度)
- 約700行の長大ファイル
- Tailwind 系クラスが多い（`w-full rounded border border-gray-300 bg-white px-3.5 py-2.5...`等）
- `space-y-1 text-sm text-gray-600` などがある
- 方針: `form-control` に置換、`space-y-1` → `vstack gap-1`、`text-gray-600` → `text-muted`

### [ ] 2. 残る Tailwind クラスを持つテンプレートの整理 (中優先度)
以下のファイルにまだ `form-input`/Tailwind クラスが残っている：
- `item/template/draftConfirm.php` - ドラフト確認
- `item/template/draftList.php` - ドラフト一覧（`w-full table-auto`等）
- `authExt/template/provider_select.php`
- `verify/template/start.php`
- `member/template/add.php`, `edit.php`
- `admin/template/ai-settings.php`, `feature-flags.php`

### [ ] 3. `root/template/_layout/header.php` と `sidebar.php` の見直し (低優先度)
- `header.php` はTailAdmin系クラスを使うが、root.phpでは使われていない（デッドコードの可能性）
- 確認して不要なら削除、使っているなら Bootstrap に整理

### [ ] 4. provider_form.php の DELETE UI改善 (セキュリティ)
- `authExt/ProviderView.php` line 84-90: GETリクエストでDELETE実行（CSRF リスク）
- 確認ダイアログまたはPOST form に変更すべき

---

## 📁 現在の作業ファイル状態

| ファイルパス | 状態 |
|-------------|------|
| flow.json | ✅ 完成 |
| start/template/menu.php | ✅ 完成 |
| auth/template/provider_new.php | ✅ Tabler対応済み |
| item/template/register.php | ✅ Tabler対応済み |
| category/template/edit.php | ✅ Tabler対応済み |
| barcode/template/sheet.php | ✅ Tailwind CDN削除・Bootstrap化 |
| label/template/wizard.php | ✅ Bootstrap化 |
| shelf/template/simple.php | ✅ Tailwind CDN削除・Bootstrap化 |
| item/template/fromBarcode.php | ✅ Bootstrap化 |
| authExt/template/provider_form.php | ⚠️ 要修正（Tailwindクラス多数） |
| item/template/addFromImage.php | ⚠️ Tailwindクラス（空で動作するが表示崩れの可能性） |

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

---

## 📝 次のセッションを始めるときは

「前回の続きをお願いします。`/home/schicksal/domains/saso.sksl.jp/public_html/HANDOFF.md` を読んで、残タスクを続けてください。`authExt/template/provider_form.php` のTabler対応から始めてください。」
