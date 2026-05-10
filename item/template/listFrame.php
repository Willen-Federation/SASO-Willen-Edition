<?php $this->content = function($v) {
  $sortLink = static function (string $col) use ($v): array {
    $base = './'.$v->request.'/sortby/'.$col.'/direction/';
    return [
      'desc' => $base.'desc/'.$v->searchUrl,
      'asc'  => $base.'asc/'.$v->searchUrl,
    ];
  };
  $sortConcatId = $sortLink('concatId');
  $sortCategory = $sortLink('categoryId');
  $sortPrice    = $sortLink('price');
  $sortCreate   = $sortLink('createAt');
  $sortUpdate   = $sortLink('updateAt');
?>

<div class="mt-8 rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
  <div class="hidden" id="current"><?php echo $v->request; ?></div>

  <!-- Search bar -->
  <div class="flex flex-wrap items-center gap-3 border-b border-stroke px-6 py-4 dark:border-strokedark">
    <div class="relative grow max-w-md">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
        <?php ui('iconHeroicon', ['name' => 'search', 'class' => 'h-4 w-4']); ?>
      </div>
      <input type="text" id="search" maxlength="50"
             class="form-input pl-10"
             placeholder="<?php echo ui_attr(__('ui.item.search_placeholder', [], null, '商品名')); ?>"
             value="<?php echo htmlspecialchars(urldecode($v->search), ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <button id="searchButton" type="button"
            class="inline-flex items-center justify-center gap-2 rounded bg-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-opacity-90 transition">
      <?php ui('iconHeroicon', ['name' => 'search', 'class' => 'h-4 w-4']); ?>
      <?php echo ui_text(__('ui.common.search', [], null, '検索')); ?>
    </button>
    <a href="./<?php echo ui_attr($v->request); ?>/"
       class="inline-flex items-center justify-center rounded border border-stroke px-4 py-2.5 text-sm font-medium text-body hover:bg-gray-100 dark:border-strokedark dark:text-bodydark dark:hover:bg-meta-4 transition">
      <?php echo ui_text(__('ui.common.search_clear', [], null, '検索解除')); ?>
    </a>
  </div>

  <!-- Items table -->
  <div class="max-w-full overflow-x-auto">
    <table class="w-full table-auto">
      <thead>
        <tr class="bg-gray-2 text-left dark:bg-meta-4">
          <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white xl:pl-7">
            <?php echo ui_text(__('ui.item.col.id', [], null, '商品番号')); ?>
            <a href="<?php echo ui_attr($sortConcatId['desc']); ?>" class="ml-1 text-body hover:text-primary" aria-label="<?php echo ui_attr(__('ui.common.sort_desc', [], null, 'Sort descending')); ?>">▼</a>
            <a href="<?php echo ui_attr($sortConcatId['asc']); ?>"  class="text-body hover:text-primary" aria-label="<?php echo ui_attr(__('ui.common.sort_asc', [], null, 'Sort ascending')); ?>">▲</a>
          </th>
          <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">
            <?php echo ui_text(__('ui.item.col.name', [], null, '商品名')); ?>
          </th>
          <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">
            <?php echo ui_text(__('ui.item.col.category', [], null, '分類')); ?>
            <a href="<?php echo ui_attr($sortCategory['desc']); ?>" class="ml-1 text-body hover:text-primary">▼</a>
            <a href="<?php echo ui_attr($sortCategory['asc']); ?>"  class="text-body hover:text-primary">▲</a>
          </th>
          <th class="min-w-[100px] py-4 px-4 font-medium text-black dark:text-white">
            <?php echo ui_text(__('ui.item.col.price', [], null, '価格')); ?>
            <a href="<?php echo ui_attr($sortPrice['desc']); ?>" class="ml-1 text-body hover:text-primary">▼</a>
            <a href="<?php echo ui_attr($sortPrice['asc']); ?>"  class="text-body hover:text-primary">▲</a>
          </th>
          <th class="py-4 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.item.col.plastic', [], null, 'プラ')); ?></th>
          <th class="py-4 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.item.col.note', [], null, '付記')); ?></th>
          <th class="py-4 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.item.col.paper', [], null, '紙')); ?></th>
          <th class="py-4 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.item.col.note', [], null, '付記')); ?></th>
          <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">
            <?php echo ui_text(__('ui.item.col.created', [], null, '登録日')); ?>
            <a href="<?php echo ui_attr($sortCreate['desc']); ?>" class="ml-1 text-body hover:text-primary">▼</a>
            <a href="<?php echo ui_attr($sortCreate['asc']); ?>"  class="text-body hover:text-primary">▲</a>
          </th>
          <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">
            <?php echo ui_text(__('ui.item.col.updated', [], null, '更新日')); ?>
            <a href="<?php echo ui_attr($sortUpdate['desc']); ?>" class="ml-1 text-body hover:text-primary">▼</a>
            <a href="<?php echo ui_attr($sortUpdate['asc']); ?>"  class="text-body hover:text-primary">▲</a>
          </th>
          <th class="py-4 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.item.col.color', [], null, '色')); ?></th>
          <th class="py-4 px-4 font-medium text-black dark:text-white"><?php echo ui_text(__('ui.item.col.size', [], null, 'サイズ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php ($v->inside)('item', 'listContents', $v->isArchive); ?>
      </tbody>
    </table>
  </div>

  <div class="border-t border-stroke px-6 py-4 dark:border-strokedark">
    <?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>
  </div>
</div>

<?php }; ?>
