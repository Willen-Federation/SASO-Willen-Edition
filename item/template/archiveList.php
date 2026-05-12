<?php $this->title = 'アーカイブ一覧'; ?>
<?php $this->content = function($v) { ?>


<?php ($v->inside)('item', 'listFrame', $v->isArchive); ?>

<?php }; ?>
