<?php $this->title = __('ui.sidebar.item_register', [], null, 'Register product'); ?>
<?php $this->content = function ($v) { ?>

<?php
  ui('card', [
    'title' => __('ui.item.register.title', [], null, 'Register a new product'),
    'body'  => function () { ?>
      <form method="post" action="./item/add/" class="space-y-4">
        <?php ui('formField', [
          'name'        => 'itemName',
          'label'       => __('ui.item.field.name', [], null, 'Product name'),
          'required'    => true,
          'help'        => __('ui.item.field.name_help', [], null, 'Up to 50 characters.'),
          'placeholder' => __('ui.item.field.name_ph', [], null, 'Cotton T-shirt'),
        ]); ?>

        <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
          <legend class="px-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">
            <?php echo ui_text(__('ui.item.field.category', [], null, 'Category')); ?>
          </legend>
          <div id="category">
            <div id="appendingParentInputs" class="mb-2"></div>
            <button id="appendingParent" type="button" class="btn btn-secondary btn-sm">+</button>
            <div id="categoriesRoot" class="mt-2"></div>
            <p class="mt-3 text-theme-sm text-gray-600 dark:text-gray-400">
              <?php echo ui_text(__('ui.item.field.selected_category', [], null, 'Selected category:')); ?>
              <span class="categoryPath categoryPathChangable font-medium text-brand-600 dark:text-brand-400"></span>
              <button type="button" class="hidden btn btn-ghost btn-sm" id="deselectCategory">
                <?php echo ui_text(__('ui.item.field.deselect_category', [], null, 'Deselect')); ?>
              </button>
            </p>
          </div>
          <input type="hidden" name="categoryId" id="categoryId" value="">
        </fieldset>

        <?php ui('formField', [
          'name'        => 'price',
          'label'       => __('ui.item.field.price', [], null, 'Price'),
          'help'        => __('ui.item.field.price_help', [], null, 'Up to 9 digits, comma-separated allowed.'),
          'placeholder' => '1,200',
        ]); ?>

        <?php ui('formField', [
          'name'     => 'colorName',
          'label'    => __('ui.item.field.colors', [], null, 'Colors'),
          'required' => true,
          'help'     => __('ui.item.field.colors_help', [], null, 'Comma-separated for multiple values.'),
        ]); ?>

        <?php ui('formField', [
          'name'     => 'sizeName',
          'label'    => __('ui.item.field.sizes', [], null, 'Sizes'),
          'required' => true,
          'help'     => __('ui.item.field.sizes_help', [], null, 'Comma-separated for multiple values. colors × sizes ≤ 100.'),
        ]); ?>

        <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
          <legend class="px-2 text-theme-sm font-medium text-gray-700 dark:text-gray-300">
            <?php echo ui_text(__('ui.item.field.packaging', [], null, 'Packaging')); ?>
          </legend>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="form-label inline-flex items-center gap-2">
                <input type="checkbox" name="pla" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span><?php echo ui_text(__('ui.item.field.plastic', [], null, 'Plastic')); ?></span>
              </label>
              <input type="text" name="plaNote" maxlength="50" class="form-input mt-1"
                     placeholder="<?php echo ui_attr(__('ui.item.field.plastic_note', [], null, 'Note (optional)')); ?>">
            </div>
            <div>
              <label class="form-label inline-flex items-center gap-2">
                <input type="checkbox" name="paper" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                <span><?php echo ui_text(__('ui.item.field.paper', [], null, 'Paper')); ?></span>
              </label>
              <input type="text" name="paperNote" maxlength="50" class="form-input mt-1"
                     placeholder="<?php echo ui_attr(__('ui.item.field.paper_note', [], null, 'Note (optional)')); ?>">
            </div>
          </div>
        </fieldset>

        <div class="flex justify-end gap-2">
          <?php ui('button', [
            'label'   => __('ui.button.cancel', [], null, 'Cancel'),
            'variant' => 'secondary',
            'type'    => 'link',
            'href'    => './',
          ]); ?>
          <?php ui('button', [
            'label'   => __('ui.item.register.submit', [], null, 'Register product'),
            'type'    => 'submit',
            'variant' => 'primary',
          ]); ?>
        </div>
      </form>
    <?php },
  ]);
?>

<?php }; ?>
