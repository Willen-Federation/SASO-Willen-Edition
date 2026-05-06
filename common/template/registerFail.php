<?php $this->title = '登録失敗'; ?>
<?php $this->content = function($v) { ?>

<p>入力が正しくありません : </p>
<p><?php echo match($v->errorMessage) {
    //item\\RegisterConfirm
    'invalid pla note.' => 'プラの付記は50文字以内として下さい。',
    'invalid paper note.' => '紙の付記は50文字以内として下さい。',
    'invalid color code.' => '色名は1つから最大100個まで入力できます。',
    'invalid color name.' => '色名はそれぞれ50字以内として下さい。',
    'color is nothing.' => '色名が未入力です。',
    'invalid size code.' => 'サイズ名は1つから最大100個まで入力できます。',
    'invalid size name.' => 'サイズ名はそれぞれ50字以内として下さい。',
    'invalid size order number.' => 'サイズ名は1つから最大100個まで入力できます。',
    'size is nothing.' => 'サイズ名が未入力です。',
    'invalid item.' => '商品名は50字以内で入力して下さい。',
    //feature\\Amount
    'invalid input.'=>'何らかの入力エラーがあります。',
    //shelf\\Multi
    'invalid mins input.'=>'最小値は英数のみです。',
    'invalid page.'=>'ページ数が正しくありません。',
    //shelf\\Single
    'invalid single shelf input.'=>'棚番は半角英数とハイフンのみ使用できます。',
    //label\\Register
    'length is invalid.'=>'A4サイズに対して寸法が矛盾しています。',
    //label\\Delete
    'label not found.'=>'ラベルが見つかりません。',
    //item\\Archive
    'archive note is invalid or item is not found.'=>'アーカイブ理由は50字以内で入力して下さい。',
    //item\\ArchivedAll
    'item id is invalid.'=>'商品IDが不正です。',
    'some item has archived.'=>'既にアーカイブされている商品が含まれています。',
    'archive note is invalid.'=>'アーカイブ理由は50字以内で入力して下さい。',
    //item\\ChangeCategory
    'item or category are not found.'=>'商品か分類が見つかりません。',
    //item\\ChangePrice
    'item is not found or invalid price.'=>'価格は9桁以下にして下さい。',
    //item\\ChangeSizeOrder
    'item is not found.'=>'商品がありません。',
    //item\\Reproduction
    'fail to reproduction.'=>'復刻に失敗ました。',
    //item\\AddFeature
    'size or color is too many.'=>'サイズまたは色が多すぎます。',
    'color is invalid.'=>'各色50字以内として下さい。',
    'size is invalid.'=>'各サイズ50字以内として下さい。',
    default => '何らかの入力エラーがあります。'.htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8'),
}; ?></p>
<p><a href="<?php echo htmlspecialchars('./'.$v->start, ENT_QUOTES, 'UTF-8'); ?>">登録画面</a></p>

<?php }; ?>
