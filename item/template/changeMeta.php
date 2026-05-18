<?php $this->title = '識別コード・備考の変更'; ?>
<?php $this->content = function($v) { ?>

<div class="mx-auto max-w-md rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">識別コード・備考の変更</h2>
  </div>
  <div class="px-5 py-5">
    <form method="post" action="./item/changeMeta/item/<?php echo htmlspecialchars($v->item->id, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">

      <div class="mb-4">
        <label for="metaJan" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">JANコード</label>
        <input id="metaJan" type="text" name="janCode" class="form-input w-full"
               maxlength="32" inputmode="numeric" autocomplete="off"
               value="<?php echo htmlspecialchars((string)($v->item->janCode ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。空欄で消去できます。</p>
      </div>

      <div class="mb-4">
        <label for="metaIsbn" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">ISBNコード</label>
        <input id="metaIsbn" type="text" name="isbnCode" class="form-input w-full"
               maxlength="32" autocomplete="off"
               value="<?php echo htmlspecialchars((string)($v->item->isbnCode ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">任意。空欄で消去できます。</p>
      </div>

      <div class="mb-5">
        <label for="metaNote" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">その他の備考</label>
        <textarea id="metaNote" name="note" class="form-input w-full" rows="3" maxlength="255"><?php echo htmlspecialchars((string)($v->item->note ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">255字まで。</p>
      </div>

      <button type="submit" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        変更
      </button>
    </form>
  </div>
</div>

<?php }; ?>
