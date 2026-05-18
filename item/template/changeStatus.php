<?php $this->title = 'ステータス変更'; ?>
<?php $this->content = function($v) {
    $statuses = [
        'active'       => 'アクティブ',
        'archived'     => 'アーカイブ',
        'discontinued' => '廃盤',
        'pending'      => '保留中',
        'in_storage'   => '保管中',
        'in_use'       => '利用中',
        'for_sale'     => '販売中',
        'reserved'     => '仮押さえ',
        'shipped'      => '発送済み',
    ];
    $current = $v->item->status ?? 'active';
?>

<div class="mx-auto max-w-md rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
    <h2 class="font-semibold" style="color:var(--saso-text)">ステータス変更</h2>
  </div>
  <div class="px-5 py-5">
    <form method="post" action="./item/changeStatus/item/<?php echo htmlspecialchars($v->item->id, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
      <div class="mb-5">
        <label for="statusSelect" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
          ステータス
        </label>
        <select id="statusSelect" name="status" class="form-input w-full">
          <?php foreach($statuses as $value => $label): ?>
          <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                  <?php echo $current === $value ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        変更
      </button>
    </form>
  </div>
</div>

<?php }; ?>
