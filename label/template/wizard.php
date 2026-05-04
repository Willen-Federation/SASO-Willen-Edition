<?php $this->title = __('ui.label_wizard.title', [], null, 'Print → Register'); ?>
<?php $this->content = function ($v) { ?>

<div class="row g-3">

  <div class="col-lg-4">
    <?php ui('card', [
      'title' => '1. ' . __('ui.label_wizard.step1', [], null, 'Pick a label sheet'),
      'body'  => function () use ($v) { ?>
        <p class="mb-3 small text-muted">
          <?php echo ui_text(__('ui.label_wizard.step1_help', [], null, 'Choose the printer sheet you will load. The system reserves codes for that sheet only.')); ?>
        </p>
        <?php if (empty($v->sheets)): ?>
          <?php ui('alert', ['variant' => 'warning', 'body' => __('ui.label_wizard.no_sheets', [], null, 'No label sheet layouts found. Please contact your administrator.')]); ?>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($v->sheets as $s): ?>
          <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">
            <span class="fw-medium"><?php echo ui_text($s->code); ?></span>
            <span class="text-muted small flex-fill mx-3"><?php echo ui_text($s->product_name_ja); ?></span>
            <span class="badge bg-primary"><?php echo (int)$s->columns; ?>×<?php echo (int)$s->rows; ?></span>
            <?php if ($s->is_verified): ?>
            <span class="ms-2 badge bg-success">✓</span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      <?php },
    ]); ?>
  </div>

  <div class="col-lg-4">
    <?php ui('card', [
      'title' => '2. ' . __('ui.label_wizard.step2', [], null, 'Mint &amp; print'),
      'body'  => function () use ($v) { ?>
        <p class="mb-3 small text-muted">
          <?php echo ui_text(__('ui.label_wizard.step2_help', [], null, 'Pick a quantity, then download the PDF and load the printer.')); ?>
        </p>
        <form method="post" action="./api/v1/barcodes/mint" class="vstack gap-3">
          <?php
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
            'name'    => 'count',
            'label'   => __('ui.label_wizard.count', [], null, 'How many labels?'),
            'type'    => 'select',
            'options' => [12 => '12', 30 => '30', 60 => '60', 120 => '120'],
            'value'   => 12,
          ]); ?>
          <?php ui('button', [
            'label'      => __('ui.label_wizard.print', [], null, 'Mint & download PDF'),
            'type'       => 'submit',
            'variant'    => 'primary',
            'extraClass' => 'w-100',
          ]); ?>
        </form>
      <?php },
    ]); ?>
  </div>

  <div class="col-lg-4">
    <?php ui('card', [
      'title' => '3. ' . __('ui.label_wizard.step3', [], null, 'Attach items'),
      'body'  => function () { ?>
        <p class="mb-3 small text-muted">
          <?php echo ui_text(__('ui.label_wizard.step3_help', [], null, 'Scan a printed label and pair it to the new product information.')); ?>
        </p>
        <?php ui('alert', [
          'variant' => 'info',
          'body'    => __('ui.label_wizard.step3_note', [], null, 'Pending barcodes are listed under "Pending labels" in the sidebar after minting.'),
        ]); ?>
        <div class="mt-3">
          <?php ui('button', [
            'label'      => __('ui.label_wizard.go_to_pending', [], null, 'Open pending labels'),
            'type'       => 'link',
            'href'       => './label/pending/',
            'variant'    => 'secondary',
            'extraClass' => 'w-100',
          ]); ?>
        </div>
      <?php },
    ]); ?>
  </div>

</div>

<?php }; ?>
