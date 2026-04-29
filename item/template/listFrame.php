<?php $this->content = function($v) { ?>

<div class="hidden" id="current"><?php echo $v->request; ?></div>

<div class="rounded-sm border border-stroke bg-white p-6 shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
  <label for="search" class="mb-2.5 block font-medium text-black dark:text-white">
    <?php echo __('ui.search.item_name_input', [], null, '商品名'); ?>
  </label>
  <div class="relative flex gap-3">
    <div class="relative flex-1">
      <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-gray-400">
        <?php ui('iconHeroicon', ['name' => 'search', 'class' => 'h-5 w-5']); ?>
      </span>
      <input id="search" type="text" maxlength="50" 
             placeholder="<?php echo __('ui.search.item_name_placeholder', [], null, '商品名で検索'); ?>"
             value="<?php echo urldecode($v->search); ?>"
             class="w-full rounded border border-stroke bg-transparent py-3 pl-11.5 pr-4.5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
    </div>
    <button id="searchButton" type="button" class="inline-flex items-center justify-center rounded bg-primary px-6 py-3 font-medium text-white hover:bg-opacity-90 transition whitespace-nowrap">
      <?php echo __('ui.search.execute', [], null, '検索'); ?>
    </button>
    <a href="./<?php echo $v->request; ?>/" class="inline-flex items-center justify-center rounded border border-stroke bg-white px-4 py-3 font-medium text-gray-700 hover:bg-gray-50 dark:border-strokedark dark:bg-transparent dark:text-gray-300 dark:hover:bg-white/5 transition whitespace-nowrap">
      <?php echo __('ui.search.clear', [], null, '検索解除'); ?>
    </a>
  </div>
</div>

<div class="w-full rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark overflow-hidden mb-6">
  <div class="w-full overflow-x-auto">
    <table class="w-full table-auto text-left text-sm">
      <thead class="bg-gray-2 text-black dark:bg-meta-4 dark:text-white">
        <tr class="border-b border-stroke dark:border-strokedark">
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">
            商品番号
            <div class="inline-flex flex-col ml-1 align-middle">
              <a href="./<?php echo $v->request; ?>/sortby/concatId/direction/asc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▲</a>
              <a href="./<?php echo $v->request; ?>/sortby/concatId/direction/desc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▼</a>
            </div>
          </th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">商品名</th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">
            分類
            <div class="inline-flex flex-col ml-1 align-middle">
              <a href="./<?php echo $v->request; ?>/sortby/categoryId/direction/asc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▲</a>
              <a href="./<?php echo $v->request; ?>/sortby/categoryId/direction/desc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▼</a>
            </div>
          </th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">
            価格
            <div class="inline-flex flex-col ml-1 align-middle">
              <a href="./<?php echo $v->request; ?>/sortby/price/direction/asc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▲</a>
              <a href="./<?php echo $v->request; ?>/sortby/price/direction/desc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▼</a>
            </div>
          </th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">プラ</th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">付記</th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">紙</th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">付記</th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">
            登録日
            <div class="inline-flex flex-col ml-1 align-middle">
              <a href="./<?php echo $v->request; ?>/sortby/createAt/direction/asc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▲</a>
              <a href="./<?php echo $v->request; ?>/sortby/createAt/direction/desc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▼</a>
            </div>
          </th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">
            更新日
            <div class="inline-flex flex-col ml-1 align-middle">
              <a href="./<?php echo $v->request; ?>/sortby/updateAt/direction/asc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▲</a>
              <a href="./<?php echo $v->request; ?>/sortby/updateAt/direction/desc/<?php echo $v->searchUrl; ?>" class="hover:text-primary leading-none text-xs">▼</a>
            </div>
          </th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">色</th>
          <th scope="col" class="px-4 py-3 font-semibold whitespace-nowrap">サイズ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50 text-gray-800 dark:text-white/90">
        <?php ($v->inside)('item', 'listContents', $v->isArchive); ?>
      </tbody>
    </table>
  </div>
</div>

<?php ($v->inside)('item', 'pagination', $v->isArchive, $v->request); ?>

<?php }; ?>
