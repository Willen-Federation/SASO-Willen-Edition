# Bootstrap から TailAdmin へのマイグレーション ガイド

既存の管理ページを Bootstrap 5 から TailAdmin/Tailwind CSS に更新する場合、このガイドは Bootstrap クラスを TailAdmin に置き換える際のリファレンスを提供します。

## クイック リファレンス

### ナビゲーション＆ブレッドクラム
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.breadcrumb` | `.breadcrumb` | Flex レイアウト |
| `.breadcrumb-item` | `.breadcrumb-item` | リスト アイテム |
| `.breadcrumb-item.active` | `.breadcrumb-item.active` | 現在のページ |

### カード
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.card` | `.card` | 丸い境界線、影、ダーク モード対応 |
| `.card-header` | `.card-header` | 上部セクション |
| `.card-body` | `.card-body` | コンテンツ セクション |

### フォーム
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.form-label` | `.form-label` | ラベル |
| `.form-control` | `.form-input` | テキスト入力 |
| `.form-select` | `.form-select` | ドロップダウン |
| `.form-check` | `.form-check` | チェックボックス グループ |
| `.form-check-input` | `.form-check-input` | チェックボックス入力 |
| `.form-check-label` | `.form-check-label` | チェックボックス ラベル |
| `.form-text` | `.form-text` | ヘルパー テキスト |

### ボタン
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.btn .btn-primary` | `.btn-primary` | 直接使用 |
| `.btn .btn-secondary` | `.btn-secondary` | セカンダリ |
| `.btn .btn-success` | `.btn-success` | 成功ボタン |
| `.btn .btn-danger` | `.btn-danger` | 危険ボタン |
| `.btn .btn-warning` | `.btn-warning` | 警告ボタン |
| `.btn .btn-sm` | `.btn-sm` | 小さいボタン |

### アラート
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.alert` | `.alert` | ベース アラート |
| `.alert-success` | `.alert-success` | 成功アラート |
| `.alert-warning` | `.alert-warning` | 警告アラート |
| `.alert-danger` | `.alert-danger` | エラー アラート |
| `.alert-dismissible` | 削除 | Alpine.js を使用 |
| `.fade` | `.fade` | トランジション |
| `.show` | `.show` | 可視状態 |
| `.btn-close` | `.btn-close` | 閉じるボタン |

### バッジ
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.badge` | `.badge` | インライン ステータス指標 |
| `.badge.bg-primary` | `.badge.bg-primary` | 青いバッジ |
| `.badge.bg-success` | `.badge.bg-success` | 緑のバッジ |
| `.badge.bg-danger` | `.badge.bg-danger` | 赤いバッジ |
| `.badge.bg-warning` | `.badge.bg-warning` | 黄色いバッジ |

### テーブル
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.table` | `.table` | ベース テーブル |
| `.table-striped` | `.table-striped` | ストライプ行 |
| `.table-hover` | `.table-hover` | ホバー効果 |
| `.table-dark` | `.table-dark` | ダーク ヘッダー |
| `.table-responsive` | `.table-responsive` | レスポンシブ |

### レイアウト
| Bootstrap | TailAdmin | 注記 |
|-----------|-----------|------|
| `.d-flex` | `.d-flex` | Flexbox 有効化 |
| `.justify-content-between` | `.justify-content-between` | 項目間のスペース |
| `.align-items-center` | `.align-items-center` | 垂直中央揃え |
| `.gap-3` | `.gap-3` | 12px ギャップ |
| `.gap-4` | `.gap-4` | 16px ギャップ |

## マイグレーション戦略

### ステップ 1: CSS ファイルを更新
新しい TailAdmin コンポーネント クラスを `/css/input.css` に追加します。（すでに完了しています）

### ステップ 2: HTML 構造を更新
Bootstrap クラスを TailAdmin に置き換えます：

```php
<!-- 前（Bootstrap） -->
<div class="card mb-4">
  <div class="card-header fw-bold">タイトル</div>
  <div class="card-body">コンテンツ</div>
</div>

<!-- 後（TailAdmin） -->
<div class="card mb-4">
  <div class="card-header fw-bold">タイトル</div>
  <div class="card-body">コンテンツ</div>
</div>
<!-- HTML の変更は不要 - CSS が処理します！ -->
```

### ステップ 3: JavaScript を更新
Bootstrap JavaScript を Alpine.js に置き換えます：

```php
<!-- 前（Bootstrap） -->
<div class="alert alert-success alert-dismissible fade show">
  メッセージ
  <button class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- 後（Alpine.js） -->
<div class="alert alert-success fade show" x-data="{ show: true }" x-show="show">
  <div class="flex items-start justify-between gap-3">
    <span>メッセージ</span>
    <button class="btn-close" @click="show = false"></button>
  </div>
</div>
```

### ステップ 4: ダーク モードをテスト
1. ヘッダーでダーク モードを切り替え
2. すべての要素が表示されることを確認
3. コントラスト比が WCAG AA を満たすことを確認

### ステップ 5: レスポンシブ性をテスト
1. モバイル（375px）でテスト
2. タブレット（768px）でテスト
3. デスクトップ（1280px）でテスト

---

詳細は英語版ドキュメント [Migration Guide](migration-guide.md) を参照してください。
