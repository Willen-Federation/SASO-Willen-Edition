<?php $this->title = '商品情報編集'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item"><a href="./item/start/item/<?php echo $v->item->id; ?>">商品情報</a></li>
<li class="breadcrumb-item active">商品情報編集</li>
</ol>
</nav>

<table class="table table-striped">
<?php ($v->inside)('item', 'head'); ?>
<?php ($v->inside)('item', 'row', $v->item); ?>
</table>

<?php ($v->inside)('item', 'changeCategory'); ?>
<hr>
<?php ($v->inside)('item', 'changePrice'); ?>
<hr>
<?php ($v->inside)('item', 'changeSizeOrder'); ?>
<hr>
<?php ($v->inside)('item', 'archive'); ?>

<?php }; ?>