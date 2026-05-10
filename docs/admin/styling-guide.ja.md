# スタイリング ガイド

TailAdmin と Tailwind CSS を使用して管理ページをスタイリングするためのベストプラクティス。

## カラー パレット

### セマンティック カラー
一貫した意味のセマンティック カラーを使用してください：

- **Primary（青）** - メイン アクション、リンク、フォーカス状態
- **Success（緑）** - 肯定的なアクション、確認、有効状態
- **Danger（赤）** - 破壊的なアクション、エラー、無効状態
- **Warning（黄色）** - 注意、保留中の状態、警告
- **Info（水色）** - 情報メッセージ

### CSS 変数
すべての色は CSS カスタム プロパティを使用しており、ダーク モードをサポートしています。

## スペーシング

Tailwind で定義されたスペーシング スケールに従います：

```
1 ユニット = 0.25rem = 4px
```

### 推奨される使用方法
- **カード**: `.p-6`（24px パディング）
- **フォーム グループ**: `.mb-4`（16px 下部マージン）
- **セクション**: `.mb-6`（24px 下部マージン）
- **ボタン グループ**: `.gap-3` または `.gap-4`（12-16px ギャップ）

## タイポグラフィ

### フォント ファミリー
すべてのテキストは、ベース スタイルで定義された日本語ファーストのフォント スタックを使用します。

### テキスト サイズ
```
text-xs = 12px
text-sm = 14px
text-base = 16px
text-lg = 18px
```

## レイアウト パターン

### 2 列フォーム
```html
<div class="row g-3">
  <div class="col-md-6">
    <label for="field1" class="form-label">フィールド 1</label>
    <input type="text" class="form-input" id="field1">
  </div>
  <div class="col-md-6">
    <label for="field2" class="form-label">フィールド 2</label>
    <input type="text" class="form-input" id="field2">
  </div>
</div>
```

### データ テーブル
```html
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <!-- テーブル コンテンツ -->
      </table>
    </div>
  </div>
</div>
```

## レスポンシブ デザイン

### ブレークポイント
```
sm  = 640px
md  = 768px   （タブレット ブレークポイント）
lg  = 1024px  （デスクトップ ブレークポイント）
xl  = 1280px  （大画面）
```

### 一般的なレスポンシブ パターン
```html
<!-- 小さい画面ではスタック、中程度の画面以上では並行 -->
<div class="row g-3">
  <div class="col-md-6">左</div>
  <div class="col-md-6">右</div>
</div>
```

## ダーク モード

### ダーク モード用の .dark: プリフィックス
```html
<div class="bg-white dark:bg-boxdark 
            border-gray-200 dark:border-gray-800
            text-black dark:text-white">
  ダーク モード対応要素
</div>
```

---

詳細は英語版ドキュメント [Styling Guide](styling-guide.md) を参照してください。
