<?php $this->title = '色・サイズ追加'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li><a href="./item/start/item/<?php echo (int)$v->item->id; ?>/" class="hover:underline" style="color:var(--saso-text-sub)">商品情報</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">色・サイズ追加</li>
  </ol>
</nav>

<div class="mb-5 overflow-x-auto rounded-2xl border"
     style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <?php ($v->inside)('item', 'head'); ?>
    <?php ($v->inside)('item', 'row', $v->item); ?>
  </table>
</div>

<div class="mx-auto max-w-lg rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold" style="color:var(--saso-text)">色・サイズを追加</h3>
  </div>
  <div class="px-5 py-5">
    <form method="post" action="./item/addFeature/item/<?php echo (int)$v->item->id; ?>">
      <div class="mb-4">
        <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">追加する色</label>
        <input type="text" name="colorName" class="form-input w-full"
               placeholder="複数の場合は半角カンマ（,）で区切ってください">
      </div>
      <div class="mb-4">
        <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">追加するサイズ</label>
        <input type="text" name="sizeName" class="form-input w-full"
               placeholder="複数の場合は半角カンマ（,）で区切ってください">
      </div>
      <p class="mb-4 text-sm" style="color:var(--saso-text-sub)">
        各色・各サイズは50字まで。色数×サイズ数 &le; 100
      </p>
      <button type="submit" class="btn btn-primary w-full">追加</button>
    </form>
  </div>
</div>

<?php }; ?>
