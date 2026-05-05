<?php $this->title = 'ホーム'; ?>
<?php $this->content = function($v) { ?>

<?php ($v->inside)('barcode', 'start'); ?>
<?php ($v->inside)('start', 'menu'); ?>
<?php ($v->inside)('item', 'listFrame'); ?>

<?php }; ?>