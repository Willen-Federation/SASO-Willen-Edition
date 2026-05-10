# 管理コンソール

SASO 管理コンソールは、システム設定、ユーザー、設定を管理するための一元化されたインターフェースです。このセクションでは、管理インターフェースのアーキテクチャ、スタイリング パターン、新しい管理ページの開発についてのベストプラクティスについて説明します。

## 概要

管理コンソールは以下で構成されています：

- **PHP** - サーバーサイドテンプレートとロジック
- **TailAdmin** - Tailwind CSS ベースのダッシュボード テンプレート
- **Alpine.js** - クライアントサイド インタラクティビティとテーマ管理
- **Material for MkDocs** - ドキュメント（このサイト）

## 主な機能

### ダークモード対応
すべての管理ページは、ヘッダーのテーマ ボタンで切り替え可能なライト モードとダーク モードをサポートしています。テーマの設定は localStorage に保存され、OS の設定と同期します。

### レスポンシブ デザイン
管理ページは、Tailwind のレスポンシブ ユーティリティ（`sm:`、`md:`、`lg:` ブレークポイント）を使用して、モバイル、タブレット、デスクトップのビューポートに適応します。

### アクセシビリティ
管理ページは WCAG 2.1 AA 標準に準拠しており、以下を含みます：

- セマンティック HTML
- キーボード ナビゲーション
- フォーカス インジケータ
- 色のコントラスト要件
- スクリーンリーダー用の ARIA ラベル

### カラー トークン
すべての色は CSS カスタム プロパティ（CSS 変数）を使用しており、ライト/ダーク モードを尊重しています：

- `--saso-text` - 主要なテキスト色
- `--saso-body` - 本体の背景色
- `--saso-card` - カード背景色
- その他多くの色（`/css/app.css` を参照）

## 管理ページ

### ビルイン管理ページ

| ページ | URL | 目的 |
|------|-----|------|
| 認証プロバイダ | `/admin/auth/` | OIDC、SAML、ローカル認証プロバイダの管理 |
| 機能フラグ | `/admin/flags/` | 機能フラグの切り替えと設定 |
| モバイル デバイス | `/admin/mobile/` | ペアリング済みモバイル デバイスとトークンの管理 |
| AI 設定 | `/admin/ai-settings/` | AI プロバイダと API キーの設定 |
| Firebase 設定 | `/admin/firebase/` | Firebase プロジェクト設定の設定 |

## アーキテクチャ

### ビュー層
管理ページは `/admin/` ディレクトリの PHP View クラスとして実装されています：

```php
<?php
namespace saso\admin;

final class AuthView implements View {
  // ロジック、HTML/テンプレート コード
}
```

### レイアウト
すべての管理ページは root レイアウト（`/root/template/root.php`）を継承します：
- サイドバー ナビゲーション
- テーマ トグルとユーザー メニュー付きのヘッダー
- メイン コンテンツ エリア
- フッター

### スタイリング
管理ページは `/css/input.css` で定義された TailAdmin コンポーネントを使用します：
- `.card`、`.card-header`、`.card-body` - カード レイアウト
- `.btn-primary`、`.btn-danger` など - ボタン スタイル
- `.form-input`、`.form-label` - フォーム要素
- `.table`、`.table-striped` - テーブル
- `.badge` - ステータス バッジ
- その他多くのコンポーネント

---

**参照：** [Components](components.ja.md) · [Styling Guide](styling-guide.ja.md) · [Dark Mode](dark-mode.ja.md) · [Migration Guide](migration-guide.ja.md) · [Accessibility](accessibility.ja.md)
