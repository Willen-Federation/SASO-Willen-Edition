<?php $this->title = 'アーカイブ'; ?>
<?php $this->content = function($v) { ?>

<div class="mx-auto max-w-md rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">アーカイブ</h2>
  </div>
  <div class="px-5 py-5">
    <form method="post" action="./item/archive/item/<?php echo htmlspecialchars($v->item->id, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
      <div class="mb-5">
        <label for="archiveNote" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          アーカイブ理由
        </label>
        <input id="archiveNote" type="text" name="archiveNote" class="form-input w-full" maxlength="50">
      </div>
      <button type="submit" class="btn btn-danger">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M10 12v6m4-6v6"/></svg>
        アーカイブ
      </button>
    </form>
  </div>
</div>

<?php }; ?>
