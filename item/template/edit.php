<?php $this->title = '商品情報編集'; ?>
<?php $this->content = function($v) { ?>


<div class="mb-5 overflow-x-auto rounded-2xl border" style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <?php ($v->inside)('item', 'head'); ?>
    <?php ($v->inside)('item', 'row', $v->item); ?>
  </table>
</div>

<?php ($v->inside)('item', 'changeCategory'); ?>
<hr class="my-5" style="border-color:var(--saso-card-bdr)">
<?php ($v->inside)('item', 'changePrice'); ?>
<hr class="my-5" style="border-color:var(--saso-card-bdr)">
<?php ($v->inside)('item', 'changeSizeOrder'); ?>
<hr class="my-5" style="border-color:var(--saso-card-bdr)">
<?php ($v->inside)('item', 'archive'); ?>

<?php }; ?>
