<?php $this->content = function($v) { ?>

<div class="rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold" style="color:var(--saso-text)">サイズ表示順変更</h3>
    <p class="mt-1 text-sm" style="color:var(--saso-text-sub)">変更後の順番を数値で指定してください（昇順）。</p>
  </div>
  <div class="px-5 py-5">
    <form method="post" action="./item/changeSizeOrder/item/<?php echo (int)$v->item->id; ?>">
      <div class="mb-4 max-w-xs space-y-2">
        <?php foreach($v->sizes as $size): ?>
        <div class="flex items-center gap-3">
          <span class="flex-1 text-sm" style="color:var(--saso-text)"><?php echo htmlspecialchars($size->name, ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="number" class="form-input w-20"
                 name="size<?php echo htmlspecialchars($size->code, ENT_QUOTES, 'UTF-8'); ?>"
                 min="0" max="99" value="<?php echo (int)$size->orderNumber; ?>">
        </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary">変更</button>
    </form>
  </div>
</div>

<?php }; ?>
