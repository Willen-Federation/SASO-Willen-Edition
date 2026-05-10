<?php $this->title = '商品情報編集'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li><a href="./item/start/item/<?php echo (int)$v->item->id; ?>/" class="hover:underline" style="color:var(--saso-text-sub)">商品情報</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">商品情報編集</li>
  </ol>
</nav>

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
