<?php $this->content = function($v) { ?>

<div class="rounded-sm border border-stroke bg-white p-6 shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
  <label for="barcodeInput" class="mb-2.5 block font-medium text-black dark:text-white">
    <?php echo __('ui.search.barcode_input', [], null, '商品バーコード入力'); ?>
  </label>
  <div class="mb-3">
    <?php
    $inputId     = 'barcodeInput';
    $buttonLabel = __('ui.scanner.open', [], null, 'Scan Barcode / QR');
    $uniqueId    = 'search_barcode';
    include __DIR__ . '/../../root/template/_components/barcodeScanner.php';
    ?>
  </div>
  <div class="relative flex gap-3">
    <div class="relative flex-1">
      <span class="absolute left-4.5 top-1/2 -translate-y-1/2 text-gray-400">
        <?php ui('iconHeroicon', ['name' => 'barcode', 'class' => 'h-5 w-5']); ?>
      </span>
      <input id="barcodeInput" type="text" maxlength="12" 
             placeholder="<?php echo __('ui.search.barcode_placeholder', [], null, '商品バーコード入力'); ?>"
             class="w-full rounded border border-stroke bg-transparent py-3 pl-11.5 pr-4.5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
    </div>
    <a id="barcodeSubmit" href="" class="inline-flex items-center justify-center rounded bg-success px-6 py-3 font-medium text-white hover:bg-opacity-90 transition whitespace-nowrap">
      <?php echo __('ui.search.barcode_show', [], null, '表示'); ?>
    </a>
  </div>
</div>

<?php }; ?>
