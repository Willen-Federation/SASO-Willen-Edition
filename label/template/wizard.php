<?php $this->title = __('ui.label_wizard.title', [], null, 'Print → Register'); ?>
<?php $this->content = function ($v) { ?>

<div class="grid gap-6 lg:grid-cols-3">
  <?php
    ui('card', [
      'title' => '1. ' . __('ui.label_wizard.step1', [], null, 'Pick a label sheet'),
      'body'  => function () use ($v) { ?>
        <p class="mb-3 text-theme-sm text-gray-500 dark:text-gray-400">
          <?php echo ui_text(__('ui.label_wizard.step1_help', [], null, 'Choose the printer sheet you will load. The system reserves codes for that sheet only.')); ?>
        </p>
        <?php if (empty($v->sheets)): ?>
          <?php ui('alert', ['variant' => 'warning', 'body' => __('ui.label_wizard.no_sheets', [], null, 'No label sheet layouts found. Please contact your administrator.')]); ?>
        <?php else: ?>
        <ul class="space-y-2 text-theme-sm">
          <?php foreach ($v->sheets as $s): ?>
          <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
            <span class="font-medium"><?php echo ui_text($s->code); ?></span>
            <span class="text-xs text-gray-500 dark:text-gray-400 flex-1 mx-3"><?php echo ui_text($s->product_name_ja); ?></span>
            <span class="ta-badge ta-badge-primary"><?php echo (int)$s->columns; ?>×<?php echo (int)$s->rows; ?></span>
            <?php if ($s->is_verified): ?>
            <span class="ml-2 ta-badge ta-badge-success">✓</span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      <?php },
    ]);
  ?>

  <?php
    ui('card', [
      'title' => '2. ' . __('ui.label_wizard.step2', [], null, 'Mint &amp; print'),
      'body'  => function () use ($v) { ?>
        <p class="mb-3 text-theme-sm text-gray-500 dark:text-gray-400">
          <?php echo ui_text(__('ui.label_wizard.step2_help', [], null, 'Pick a quantity, then download the PDF and load the printer.')); ?>
        </p>
        <form method="post" action="./api/v1/barcodes/mint" class="space-y-3">
          <?php
          // Build sheet options from DB data
          $sheetOptions = [];
          foreach ($v->sheets as $s) {
              $label = $s->code . ' (' . (int)$s->columns . '×' . (int)$s->rows . ')';
              $sheetOptions[$s->id] = $label;
          }
          if (!empty($sheetOptions)) {
              ui('formField', [
                'name'    => 'sheet_layout_id',
                'label'   => __('ui.label_wizard.sheet_type', [], null, 'Sheet type'),
                'type'    => 'select',
                'options' => $sheetOptions,
              ]);
          }
          ?>
          <?php ui('formField', [
            'name'        => 'count',
            'label'       => __('ui.label_wizard.count', [], null, 'How many labels?'),
            'type'        => 'select',
            'options'     => [12 => '12', 30 => '30', 60 => '60', 120 => '120'],
            'value'       => 12,
          ]); ?>
          <?php ui('button', [
            'label'   => __('ui.label_wizard.print', [], null, 'Mint & download PDF'),
            'type'    => 'submit',
            'variant' => 'primary',
            'extraClass' => 'w-full',
          ]); ?>
        </form>
      <?php },
    ]);
  ?>

  <?php
    ui('card', [
      'title' => '3. ' . __('ui.label_wizard.step3', [], null, 'Attach items'),
      'body'  => function () { ?>
        <p class="mb-3 text-theme-sm text-gray-500 dark:text-gray-400">
          <?php echo ui_text(__('ui.label_wizard.step3_help', [], null, 'Scan a printed label and pair it to the new product information.')); ?>
        </p>
        <?php ui('alert', [
          'variant' => 'info',
          'body'    => __('ui.label_wizard.step3_note', [], null, 'Pending barcodes are listed under "Pending labels" in the sidebar after minting.'),
        ]); ?>
        <div class="mt-4">
          <?php ui('button', [
            'label'   => __('ui.label_wizard.go_to_pending', [], null, 'Open pending labels'),
            'type'    => 'link',
            'href'    => './label/pending/',
            'variant' => 'secondary',
            'extraClass' => 'w-full',
          ]); ?>
        </div>
      <?php },
    ]);
  ?>
</div>

<?php }; ?>
