<?php
$this->title = __('ui.sidebar.search', [], null, 'Search');
$this->content = function ($v) {
?>
<?php ($v->inside)('barcode', 'start'); ?>
<?php ($v->inside)('item', 'listFrame'); ?>
<?php }; ?>
