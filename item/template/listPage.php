<?php $this->title = '商品一覧'; ?>
<?php $this->content = function($v) { ?>


<?php ($v->inside)('item', 'listFrame', $v->isArchive); ?>

<?php }; ?>
