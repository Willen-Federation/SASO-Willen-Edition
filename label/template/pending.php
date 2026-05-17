<?php $this->title = __('ui.label_pending.title', [], null, 'Pending Labels'); ?>
<?php $this->content = function ($v) { ?>

<div class="rounded-2xl border overflow-hidden"
     style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
  <div class="flex items-center justify-between gap-3 border-b px-5 py-4"
       style="border-color:var(--saso-card-bdr)">
    <h3 class="font-semibold" style="color:var(--saso-text)">
      <?php echo ui_text(__('ui.label_pending.title', [], null, 'Pending Labels')); ?>
    </h3>
    <a href="./barcode/sheet/" class="btn btn-secondary btn-sm">
      <?php echo ui_text(__('ui.label_pending.back_to_wizard', [], null, '← Back to wizard')); ?>
    </a>
  </div>

  <div class="px-5 py-5">
    <?php if (empty($v->codes)): ?>
      <div class="flex flex-col items-center gap-3 py-10" style="color:var(--saso-text-sub)">
        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
          <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
          <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
          <path d="M14 14h.01M14 17h3M17 14v7M20 17h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <p class="text-sm"><?php echo ui_text(__('ui.label_pending.empty', [], null, 'No pending labels. Mint a batch from the wizard.')); ?></p>
        <a href="./barcode/sheet/" class="btn btn-primary btn-sm">
          <?php echo ui_text(__('ui.label_pending.go_wizard', [], null, 'Go to wizard')); ?>
        </a>
      </div>
    <?php else: ?>
      <p class="mb-3 text-sm" style="color:var(--saso-text-sub)">
        <?php echo sprintf(
          ui_text(__('ui.label_pending.count', [], null, '%d pending label(s) — scan one to attach it to a product.')),
          count($v->codes)
        ); ?>
      </p>
      <div class="overflow-x-auto">
        <table class="ta-table" aria-label="Pending labels">
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
              <td><code class="font-mono text-xs"><?php echo ui_text($code->code->asString()); ?></code></td>
              <td><span class="ta-badge ta-badge-secondary"><?php echo (int) $code->batchId; ?></span></td>
              <td class="text-sm" style="color:var(--saso-text-sub)"><?php echo ui_text($code->createdAt->format('Y-m-d H:i')); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php }; ?>
