# アクセシビリティ ガイドライン

すべての管理ページは WCAG 2.1 レベル AA アクセシビリティ標準を満たす必要があります。これらのガイドラインにより、障害のある人を含むすべての人が管理ページを使用できることが保証されます。

## 標準とコンプライアンス

### WCAG 2.1 レベル AA
管理コンソールは **WCAG 2.1 レベル AA** コンプライアンスを目指しています：

- **知覚可能**: コンテンツは表示可能で識別可能
- **操作可能**: キーボード ナビゲーションとインタラクティブ制御が機能
- **理解可能**: コンテンツは明確で言語が適切
- **堅牢**: 支援技術と互換性あり

### カラー コントラスト
すべてのカラー組み合わせはコントラスト コンプライアンス用にテストされています：

- **通常のテキスト**: 最小 4.5:1 コントラスト比
- **大きいテキスト**（18pt+）: 最小 3:1 コントラスト比

## セマンティック HTML

### 適切な要素を使用
```html
<!-- ✓ 良い - セマンティック要素 -->
<button type="submit">送信</button>
<label for="email">メール</label>
<table>
  <thead><tr><th>名前</th></tr></thead>
  <tbody><tr><td>データ</td></tr></tbody>
</table>

<!-- ✗ 避ける - 非セマンティック -->
<div role="button">送信</div>
<span>メール</span>
<div><!-- テーブル構造 --></div>
```

## フォーム アクセシビリティ

### すべての入力にラベルを付ける
ラベルを常にフォーム入力に関連付けます：

```html
<!-- ✓ 良い -->
<label for="username" class="form-label">ユーザー名</label>
<input type="text" class="form-input" id="username" name="username">

<!-- ✗ 悪い - ラベルなし -->
<input type="text" placeholder="ユーザー名">
```

### 必須フィールドを示す
```html
<!-- ✓ 良い - 視覚的およびテキスト指標 -->
<label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
<input type="text" id="name" name="name" required aria-required="true">
```

### ヘルプ テキスト
```html
<label for="api_key" class="form-label">API キー</label>
<input type="password" class="form-input" id="api_key" 
  aria-describedby="api_help">
<div id="api_help" class="form-text">
  形式: sk-... （秘密に保つこと）
</div>
```

## キーボード ナビゲーション

### キーボード アクセス
すべてのインタラクティブ要素をキーボードでアクセス可能にします：

```html
<!-- ✓ 良い - キーボード アクセス可能 -->
<button type="submit" class="btn btn-primary">送信</button>
<a href="/admin/users">ユーザー管理</a>
<input type="text" class="form-input">

<!-- ✗ 悪い - キーボード アクセス不可 -->
<div class="btn" role="button">送信</div>
```

### フォーカス インジケータ
すべての要素は Tailwind を介してフォーカス インジケータを表示します。削除しないでください。

## テーブル

### セマンティック テーブル構造
```html
<!-- ✓ 良い - 適切なテーブル セマンティクス -->
<table aria-label="ユーザー リスト">
  <thead>
    <tr>
      <th scope="col">名前</th>
      <th scope="col">メール</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>John</td>
      <td>john@example.com</td>
    </tr>
  </tbody>
</table>
```

## テスト

### キーボード ナビゲーション テスト
マウスを使用しないでテストします：

1. Tab キーですべてのインタラクティブ要素をナビゲート
2. すべてのコントロールに到達可能
3. フォーカス順序は論理的
4. フォーカス インジケータは常に表示
5. Enter/Space でボタンが起動

### スクリーン リーダー テスト
スクリーン リーダー（NVDA、JAWS、VoiceOver）でテストします：

1. ページ見出しが発表される
2. フォーム ラベルが入力に関連付けられている
3. ボタンの目的は明確
4. テーブル ヘッダーが発表される
5. 画像の代替テキスト

### 色のコントラスト検証
DevTools で検証します：

1. 右クリック → インスペクト → 計算済みスタイル
2. 色のプロパティをスクロール
3. コントラスト比を確認（4.5:1 以上）

### Lighthouse 監査
DevTools で実行します：

1. F12 → Lighthouse タブ
2. アクセシビリティを選択
3. レポートを生成
4. 違反を修正

---

詳細は英語版ドキュメント [Accessibility](accessibility.md) を参照してください。
