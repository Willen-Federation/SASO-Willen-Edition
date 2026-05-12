<?php $this->title = __('ui.nav.home', [], null, 'Home'); ?>
<?php $this->content = function($v) { ?>

<?php ($v->inside)('start', 'menu'); ?>
<?php ($v->inside)('barcode', 'start'); ?>
<?php ($v->inside)('item', 'listFrame'); ?>

<?php }; ?>