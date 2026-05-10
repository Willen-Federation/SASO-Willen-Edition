# ダーク モード実装

SASO 管理ページは、シームレスなライト/ダーク テーマ切り替え機能を備えた完全なダーク モードをサポートしています。

## ダーク モードの仕組み

### テーマ トグル
ユーザーはヘッダーのテーマ ボタンを使用してダーク モードを切り替えることができます。設定は以下のように処理されます：

1. `localStorage`（キー：`saso.theme`）に保存
2. すべてのページに即座に適用
3. 最初のアクセス時に OS の設定と同期

### テーマ管理（Alpine.js）
テーマ システムは `/js/tailadmin.js` の Alpine.js によって管理されます：

```javascript
// HTML ルート要素：
<html x-data="taTheme()" :class="theme">
```

`taTheme()` Alpine コンポーネント：
- localStorage の読み書き
- OS テーマ設定を検出
- `<html>` の `.dark` クラスを更新
- ページ読み込み時のライト モード フラッシュを防止

## CSS 変数システム

すべての色は `/css/app.css` で定義された CSS カスタム プロパティを使用しています。

### ライト モード（`:root`）
```css
:root {
  --saso-text: #1e293b;
  --saso-body: #c4d4e4;
  --saso-card: #ffffff;
}
```

### ダーク モード（`.dark`）
```css
.dark {
  --saso-text: #f0f4f9;
  --saso-body: #08111f;
  --saso-card: #1a2b40;
}
```

## コンポーネント クラスでのダーク モード

### コンポーネント クラス経由（推奨）
ほとんどの TailAdmin コンポーネントは自動的にダーク モードをサポートします：

```html
<!-- ✓ 良い - コンポーネントがダーク モードを処理 -->
<div class="card">コンテンツ</div>
<button class="btn btn-primary">クリック</button>
```

### Tailwind `.dark:` プリフィックス経由
カスタム スタイルの場合、Tailwind のダーク モード プリフィックスを使用します：

```html
<div class="bg-white dark:bg-boxdark">コンテンツ</div>
<p class="text-gray-600 dark:text-gray-300">説明</p>
```

## テスト

### 手動テスト
1. ヘッダーのテーマ トグル ボタンをクリック
2. ページは即座にダーク モードに切り替わる必要があります
3. ページを再読み込み - ダーク モード設定は保存されている必要があります
4. 確認事項：
   - テキスト コントラスト
   - ボタン可視性
   - テーブル可読性
   - フォーム入力可視性
   - アラート色
   - バッジ可視性

### 色のコントラスト検証
Chrome DevTools で確認：
1. インスペクト → スタイル → コントラスト比を確認
2. Lighthouse アクセシビリティ監査
3. axe DevTools ブラウザ拡張機能

## ベストプラクティス

### ✓ ダーク モードをサポート
```html
<!-- ライトとダークの色を定義 -->
<div class="bg-white dark:bg-boxdark 
            text-black dark:text-white">
  コンテンツ
</div>
```

### ✗ ダーク モードを無視
```html
<!-- ライト モードのみ - ダーク モードで失敗 -->
<div class="bg-white text-black">テキストが見えなくなります</div>
```

---

詳細は英語版ドキュメント [Dark Mode](dark-mode.md) を参照してください。
