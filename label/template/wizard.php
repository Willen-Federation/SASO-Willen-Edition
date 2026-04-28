<?php $this->title = __('ui.label_wizard.title', [], null, 'Print → Register'); ?>
<?php $this->content = function ($v) { ?>

<div class="grid gap-6 lg:grid-cols-3">
  <?php
    ui('card', [
      'title' => '1. ' . __('ui.label_wizard.step1', [], null, 'Pick a label sheet'),
      'body'  => function () { ?>
        <p class="mb-3 text-theme-sm text-gray-500 dark:text-gray-400">
          <?php echo ui_text(__('ui.label_wizard.step1_help', [], null, 'Choose the printer sheet you will load. The system reserves codes for that sheet only.')); ?>
        </p>
        <ul class="space-y-2 text-theme-sm">
          <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700"><span class="font-medium">A_ONE_28171</span><span class="ta-badge ta-badge-primary">2×6</span></li>
          <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700"><span class="font-medium">A_ONE_28173</span><span class="ta-badge ta-badge-primary">2×5</span></li>
          <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700"><span class="font-medium">AVERY_5160</span><span class="ta-badge ta-badge-primary">3×10</span></li>
        </ul>
      <?php },
    ]);
  ?>

  <?php
    ui('card', [
      'title' => '2. ' . __('ui.label_wizard.step2', [], null, 'Mint &amp; print'),
      'body'  => function () { ?>
        <p class="mb-3 text-theme-sm text-gray-500 dark:text-gray-400">
          <?php echo ui_text(__('ui.label_wizard.step2_help', [], null, 'Pick a quantity, then download the PDF and load the printer.')); ?>
        </p>
        <form method="post" action="./api/v1/barcodes/mint" class="space-y-3">
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

<div class="mt-6">
  <?php ui('alert', [
    'variant' => 'warning',
    'title'   => __('ui.label_wizard.requirements_title', [], null, 'Required'),
    'body'    => __('ui.label_wizard.requirements_body', [], null, 'Run the M6 migrations 20260428120000–20260428120004 to create the barcode_pool, barcode_batch, label_sheet_layout, and location_map tables.'),
  ]); ?>
</div>

<?php }; ?>
