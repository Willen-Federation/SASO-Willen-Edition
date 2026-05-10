# TailAdminコンポーネント

このガイドは、管理ページのスタイリングに利用可能な TailAdmin コンポーネント クラスをカバーしています。すべてのコンポーネントは、Tailwind CSS の `.dark:` プリフィックスを介してダーク モードをサポートしています。

## コンテナとカード

### カード
関連するコンテンツをグループ化するために使用される、丸い境界線と影を持つコンテナ。

```html
<div class="card mb-4">
  <div class="card-header fw-bold">カードタイトル</div>
  <div class="card-body">
    <!-- コンテンツ -->
  </div>
</div>
```

**バリアント：**
- `.card` - 標準カード
- `.card-header` - ヘッダー セクション
- `.card-body` - コンテンツ セクション

## ボタン

### ボタン バリアント

```html
<button type="submit" class="btn btn-primary">送信</button>
<button type="button" class="btn btn-secondary">キャンセル</button>
<button type="button" class="btn btn-success">確認</button>
<button type="button" class="btn btn-danger">削除</button>
<button type="button" class="btn btn-warning">注意</button>
<button type="button" class="btn btn-outline-warning">デフォルトに設定</button>
<button type="submit" class="btn btn-sm btn-success">有効</button>
```

**色オプション：**
- `.btn-primary` - 青（デフォルト）
- `.btn-secondary` - グレー
- `.btn-success` - 緑
- `.btn-danger` - 赤
- `.btn-warning` - 黄色
- `.btn-outline-*` - 枠線バリアント

## フォーム

### フォーム要素

```html
<label for="field_name" class="form-label">フィールド名</label>
<input type="text" class="form-input" id="field_name" name="field_name">
<select class="form-select" id="field_type" name="field_type">
  <option value="">選択してください...</option>
</select>

<div class="form-check">
  <input type="checkbox" class="form-check-input" id="field_enabled">
  <label class="form-check-label" for="field_enabled">有効化</label>
</div>

<div class="form-text">このフィールドのヘルパー テキスト</div>
```

## テーブル

### ストライプ行付きテーブル

```html
<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th scope="col">列1</th>
        <th scope="col">列2</th>
      </tr>
    </thead>
    <tbody>
      <!-- 行 -->
    </tbody>
  </table>
</div>
```

**バリアント：**
- `.table` - ベース テーブル スタイル
- `.table-striped` - 交互の行背景色
- `.table-hover` - ホバー時のハイライト
- `.table-dark` - ダーク ヘッダー背景
- `.table-responsive` - 小さい画面での水平スクロール

## バッジ

### ステータス インジケータ バッジ

```html
<span class="badge bg-success">有効</span>
<span class="badge bg-warning text-dark">保留中</span>
<span class="badge bg-danger">無効</span>
```

**色オプション：**
- `.bg-primary` - 青
- `.bg-secondary` - グレー
- `.bg-success` - 緑
- `.bg-danger` - 赤
- `.bg-warning` - 黄色
- `.bg-info` - 水色

## アラート

### 通知メッセージ

```html
<div class="alert alert-success fade show" role="alert" x-data="{ show: true }" x-show="show">
  <div class="flex items-start justify-between gap-3">
    <span>操作が正常に完了しました。</span>
    <button type="button" class="btn-close" @click="show = false" aria-label="閉じる"></button>
  </div>
</div>
```

**バリアント：**
- `.alert-success` - 緑（成功）
- `.alert-danger` - 赤（エラー）
- `.alert-warning` - 黄色（警告）
- `.alert-info` - 青（情報）

## ユーティリティ

### Flexbox ユーティリティ

```html
<div class="d-flex justify-content-between align-items-center gap-4">
  <span>左側</span>
  <span>右側</span>
</div>
```

### テキスト ユーティリティ

```html
<p class="text-muted">ミュート テキスト</p>
<p class="small">小さいテキスト</p>
<p class="fw-bold">太字</p>
<p class="text-truncate">長いテキストは切り詰められます...</p>
<p class="text-danger">エラー メッセージ</p>
```

### グリッド（レスポンシブ レイアウト）

```html
<div class="row g-3">
  <div class="col-md-6">
    <!-- 中程度以上の画面で50% -->
  </div>
  <div class="col-md-6">
    <!-- 中程度以上の画面で50% -->
  </div>
</div>
```

---

詳細は英語版を参照してください。
