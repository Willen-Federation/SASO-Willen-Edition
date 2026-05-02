<?php $this->title = __('ui.sidebar.category', [], null, 'Categories'); ?>
<?php $this->content = function ($v) { ?>

<?php
  ui('card', [
    'title'   => __('ui.category.title', [], null, 'Category tree'),
    'actions' => function () {
        ui('button', [
            'id'      => 'appendingParent',
            'label'   => __('ui.category.add_root', [], null, 'Add root category'),
            'variant' => 'primary',
            'icon'    => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        ]);
    },
    'body' => function () { ?>
      <p class="mb-4 text-theme-sm text-gray-500 dark:text-gray-400">
        <?php echo ui_text(__('ui.category.help', [], null, 'Add, rename, and reorder categories. Drag is not supported yet — use the controls inline next to each row.')); ?>
      </p>
      <div id="appendingParentInputs" class="mb-4"></div>
      <div id="categoriesRoot" class="rounded-lg border border-dashed border-gray-200 p-4 text-theme-sm dark:border-gray-700">
        <p class="text-gray-400" id="categoriesEmpty">
          <?php echo ui_text(__('ui.category.empty', [], null, 'Loading…')); ?>
        </p>
      </div>
    <?php },
  ]);
?>

<?php }; ?>
