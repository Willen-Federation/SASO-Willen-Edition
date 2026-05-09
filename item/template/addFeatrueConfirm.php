<?php $this->title = '色・サイズ追加確認'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="パンくずリスト">
  <ol class="mb-5 flex items-center gap-1.5 text-sm" style="color:var(--saso-text-sub)">
    <li><a href="./" class="hover:underline" style="color:var(--saso-text-sub)">ホーム</a></li>
    <li aria-hidden="true">/</li>
    <li><a href="./item/start/item/<?php echo (int)$v->item->id; ?>/" class="hover:underline" style="color:var(--saso-text-sub)">商品情報</a></li>
    <li aria-hidden="true">/</li>
    <li><a href="./item/addFeature/item/<?php echo (int)$v->item->id; ?>/" class="hover:underline" style="color:var(--saso-text-sub)">色・サイズ追加</a></li>
    <li aria-hidden="true">/</li>
    <li aria-current="page" style="color:var(--saso-text)">色・サイズ追加確認</li>
  </ol>
</nav>

<div class="mb-5 overflow-x-auto rounded-2xl border"
     style="border-color:var(--saso-card-bdr)">
  <table class="ta-table">
    <?php ($v->inside)('item', 'head'); ?>
    <?php ($v->inside)('item', 'row', $v->item); ?>
  </table>
</div>

<?php if(!$v->isValidAmount): ?>
  <div class="ta-alert ta-alert-warning mb-4" role="alert">
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      <path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <span>追加後の色の数とサイズの数をかけて100を超えてはいけません。色数×サイズ数 &le; 100</span>
  </div>
<?php endif; ?>

<?php if(!empty($v->inputColors)): ?>
  <p class="mb-2 text-sm" style="color:var(--saso-text)">追加する色：<?php echo htmlspecialchars($v->serializedColors, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<?php if(!empty($v->inputSizes)): ?>
  <p class="mb-4 text-sm" style="color:var(--saso-text)">追加するサイズ：<?php echo htmlspecialchars($v->serializedSizes, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<div class="mx-auto max-w-lg rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="px-5 py-5">
    <form method="post" action="./item/addFeature/item/<?php echo (int)$v->item->id; ?>">
      <input type="hidden" name="colorNameConfirm" value="<?php echo htmlspecialchars($v->isValidAmount ? $v->inputColors : '', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="sizeNameConfirm" value="<?php echo htmlspecialchars($v->isValidAmount ? $v->inputSizes : '', ENT_QUOTES, 'UTF-8'); ?>">
      <div class="mb-4">
        <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">追加する色</label>
        <input type="text" name="colorName" class="form-input w-full"
               value="<?php echo htmlspecialchars($v->inputColors, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="mb-4">
        <label class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">追加するサイズ</label>
        <input type="text" name="sizeName" class="form-input w-full"
               value="<?php echo htmlspecialchars($v->inputSizes, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <p class="mb-4 text-sm" style="color:var(--saso-text-sub)">
        複数ある場合は半角カンマ（,）で区切ってください。
      </p>
      <button type="submit" class="btn btn-primary w-full">追加</button>
    </form>
  </div>
</div>

<?php }; ?>
