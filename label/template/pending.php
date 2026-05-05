<?php $this->title = __('ui.label_pending.title', [], null, 'Pending Labels'); ?>
<?php $this->content = function ($v) { ?>

<?php ui('card', [
  'title'   => __('ui.label_pending.title', [], null, 'Pending Labels'),
  'actions' => function () {
      ui('button', [
          'label'   => __('ui.label_pending.back_to_wizard', [], null, '← Back to wizard'),
          'type'    => 'link',
          'href'    => './label/wizard/',
          'variant' => 'outline-secondary',
      ]);
  },
  'body' => function () use ($v) {
      if (empty($v->codes)) { ?>
        <div class="d-flex flex-column align-items-center gap-3 py-5 text-muted">
          <i class="ti ti-barcode-off fs-1" aria-hidden="true"></i>
          <p class="mb-0"><?php echo ui_text(__('ui.label_pending.empty', [], null, 'No pending labels. Mint a batch from the wizard.')); ?></p>
          <?php ui('button', [
            'label'   => __('ui.label_pending.go_wizard', [], null, 'Go to wizard'),
            'type'    => 'link',
            'href'    => './label/wizard/',
            'variant' => 'primary',
          ]); ?>
        </div>
      <?php } else { ?>
        <p class="text-muted small mb-3">
          <?php echo sprintf(
            ui_text(__('ui.label_pending.count', [], null, '%d pending label(s) — scan one to attach it to a product.')),
            count($v->codes)
          ); ?>
        </p>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th scope="col"><?php echo ui_text(__('ui.label_pending.col_code', [], null, 'Barcode')); ?></th>
                <th scope="col"><?php echo ui_text(__('ui.label_pending.col_batch', [], null, 'Batch')); ?></th>
                <th scope="col"><?php echo ui_text(__('ui.label_pending.col_created', [], null, 'Created')); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($v->codes as $code): ?>
              <tr>
                <td><code><?php echo ui_text($code->code->asString()); ?></code></td>
                <td><span class="badge bg-secondary"><?php echo (int) $code->batchId; ?></span></td>
                <td class="text-muted small"><?php echo ui_text($code->createdAt->format('Y-m-d H:i')); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
  <?php },
]); ?>

<?php }; ?>
