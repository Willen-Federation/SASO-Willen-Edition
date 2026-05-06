<?php
$this->title = __('ui.sidebar.search', [], null, '商品検索');
$this->content = function ($v) {
?>
<?php ($v->inside)('barcode', 'start'); ?>
<?php ($v->inside)('item', 'listFrame', false); ?>
<?php }; ?>
