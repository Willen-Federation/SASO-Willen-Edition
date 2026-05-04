<?php $this->title = '商品情報編集'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item"><a href="./item/start/item/<?php echo $v->item->id; ?>">商品情報</a></li>
  <li class="breadcrumb-item active" aria-current="page">商品情報編集</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table">
      <?php ($v->inside)('item', 'head'); ?>
      <?php ($v->inside)('item', 'row', $v->item); ?>
    </table>
  </div>
</div>

<?php ($v->inside)('item', 'changeCategory'); ?>
<?php ($v->inside)('item', 'changePrice'); ?>
<?php ($v->inside)('item', 'changeSizeOrder'); ?>
<?php ($v->inside)('item', 'archive'); ?>

<?php }; ?>
