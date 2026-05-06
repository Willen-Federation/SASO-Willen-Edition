<?php $this->content = function($v) { ?>

<div class="flex items-center gap-3">
  <div class="relative grow max-w-xs">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
      <?php ui('iconHeroicon', ['name' => 'qr', 'class' => 'h-5 w-5']); ?>
    </div>
    <input id="barcodeInput" 
           type="text" 
           maxlength="12" 
           class="form-input pl-10"
           placeholder="<?php echo ui_attr(__('ui.barcode.input_placeholder', [], null, 'Input barcode')); ?>">
  </div>
  <?php ui('button', [
    'id'      => 'barcodeSubmit',
    'label'   => __('ui.common.display', [], null, 'Display'),
    'variant' => 'success',
  ]); ?>
</div>

<?php }; ?>
