<?php $this->content = function($v) { ?>

<tr>
  <td colspan="12" class="px-4 py-12 text-center">
    <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
      <?php ui('iconHeroicon', ['name' => 'archive-box', 'class' => 'h-12 w-12 mb-3 opacity-50']); ?>
      <p class="text-base font-medium">
        <?php echo __('ui.search.no_results', [], null, '該当する商品が見つかりませんでした'); ?>
      </p>
      <p class="text-sm mt-1 text-gray-400 dark:text-gray-600">
        <?php echo __('ui.search.no_results_tip', [], null, '別のキーワードやバーコードをお試しください'); ?>
      </p>
    </div>
  </td>
</tr>

<?php }; ?>
