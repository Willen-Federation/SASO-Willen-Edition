<?php
$this->title = __('ui.scan_stock.title', [], null, 'Scan to Register Stock');
$this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';

    $modeStock     = __('ui.scan_stock.mode_stock',     [], null, 'Stock In (入庫)');
    $modeShipment  = __('ui.scan_stock.mode_shipment',  [], null, 'Stock Out (出庫)');
    $modeInventory = __('ui.scan_stock.mode_inventory', [], null, 'Stocktake (棚卸)');
    $submitLabel   = __('ui.scan_stock.submit',         [], null, 'Apply');
    $title         = __('ui.scan_stock.title',          [], null, 'Scan to Register Stock');
?>

<div
  x-data="{
    mode: 'stock',
    scannedCode: '',
    quantity: 1,
    item: null,
    itemError: null,
    loading: false,
    submitting: false,
    submitSuccess: false,
    submitError: null,

    async onBarcodeDetected(code) {
      this.scannedCode = code;
      this.item = null;
      this.itemError = null;
      this.loading = true;
      try {
        const res = await fetch('./api/v1/barcode/' + encodeURIComponent(code));
        if (!res.ok) throw new Error(res.statusText);
        const data = await res.json();
        if (data.item && data.item.id) {
          this.item = data.item;
        } else {
          this.itemError = <?php echo json_encode($lang === 'ja' ? 'バーコードが見つかりません' : 'Barcode not found'); ?>;
        }
      } catch (e) {
        this.itemError = <?php echo json_encode($lang === 'ja' ? '検索エラー' : 'Lookup error'); ?>;
      } finally {
        this.loading = false;
      }
    },

    actionEndpoint() {
      if (this.mode === 'stock')     return './item/stock/';
      if (this.mode === 'shipment')  return './item/shipment/';
      if (this.mode === 'inventory') return './item/inventory/';
      return './item/stock/';
    },

    async submitStock() {
      if (!this.item) return;
      this.submitting   = true;
      this.submitSuccess = false;
      this.submitError  = null;
      try {
        const form = new FormData();
        form.append('itemId',   this.item.id);
        form.append('quantity', this.quantity);
        const res = await fetch(this.actionEndpoint(), { method: 'POST', body: form });
        if (!res.ok) throw new Error(res.statusText);
        this.submitSuccess = true;
        this.scannedCode   = '';
        this.item          = null;
        this.quantity      = 1;
      } catch (e) {
        this.submitError = <?php echo json_encode($lang === 'ja' ? '登録エラー' : 'Submit error'); ?>;
      } finally {
        this.submitting = false;
      }
    },
  }"
  @barcode-detected.window="onBarcodeDetected($event.detail.code)"
>

  <div class="mb-6">
    <h1 class="text-title-md2 font-semibold text-black dark:text-white">
      <?php echo ui_text($title); ?>
    </h1>
  </div>

  <!-- Mode selector -->
  <div class="card mb-6">
    <div class="card-body">
      <div class="flex flex-wrap gap-2" role="tablist"
           aria-label="<?php echo ui_attr($lang === 'ja' ? '登録モード' : 'Stock mode'); ?>">
        <button
          type="button"
          role="tab"
          @click="mode = 'stock'"
          :aria-selected="mode === 'stock'"
          :class="mode === 'stock' ? 'btn-primary' : 'btn-secondary'"
          class="btn"
        >
          <?php echo ui_text($modeStock); ?>
        </button>
        <button
          type="button"
          role="tab"
          @click="mode = 'shipment'"
          :aria-selected="mode === 'shipment'"
          :class="mode === 'shipment' ? 'btn-primary' : 'btn-secondary'"
          class="btn"
        >
          <?php echo ui_text($modeShipment); ?>
        </button>
        <button
          type="button"
          role="tab"
          @click="mode = 'inventory'"
          :aria-selected="mode === 'inventory'"
          :class="mode === 'inventory' ? 'btn-primary' : 'btn-secondary'"
          class="btn"
        >
          <?php echo ui_text($modeInventory); ?>
        </button>
      </div>
    </div>
  </div>

  <!-- Scanner + manual input -->
  <div class="card mb-6">
    <div class="card-header">
      <h2 class="font-semibold text-black dark:text-white">
        <?php echo ui_text($lang === 'ja' ? 'バーコードをスキャン' : 'Scan Barcode'); ?>
      </h2>
    </div>
    <div class="card-body">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <div class="flex-1">
          <label for="scan-stock-barcode" class="form-label">
            <?php echo ui_text($lang === 'ja' ? 'バーコード' : 'Barcode'); ?>
          </label>
          <div class="flex gap-2">
            <?php
            $inputId     = 'scan-stock-barcode';
            $buttonLabel = __('ui.scanner.open', [], null, 'Scan Barcode / QR');
            $uniqueId    = 'scanstock';
            include __DIR__ . '/../../root/template/_components/barcodeScanner.php';
            ?>
            <input
              id="scan-stock-barcode"
              x-model="scannedCode"
              type="text"
              class="form-input flex-1"
              placeholder="<?php echo ui_attr($lang === 'ja' ? 'バーコード番号' : 'Barcode number'); ?>"
              @keydown.enter.prevent="onBarcodeDetected(scannedCode)"
              autocomplete="off"
            >
            <button
              type="button"
              @click="onBarcodeDetected(scannedCode)"
              class="btn-primary px-4"
              :disabled="loading || !scannedCode.trim()"
            >
              <span x-show="!loading">
                <?php echo ui_text($lang === 'ja' ? '検索' : 'Find'); ?>
              </span>
              <span x-show="loading" class="flex items-center gap-1">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </span>
            </button>
          </div>
          <p x-show="itemError" x-text="itemError" class="mt-2 text-sm text-error-500" role="alert"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Item info + quantity form -->
  <div x-show="item" class="card mb-6">
    <div class="card-body">

      <!-- Item summary -->
      <div class="mb-4 flex items-start gap-4">
        <div class="flex-1">
          <p class="font-semibold text-black dark:text-white" x-text="item && item.name"></p>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            ID: <span x-text="item && item.id"></span>
          </p>
        </div>
      </div>

      <!-- Quantity input -->
      <div class="mb-4">
        <label for="scan-stock-qty" class="form-label">
          <?php echo ui_text($lang === 'ja' ? '数量' : 'Quantity'); ?>
        </label>
        <input
          id="scan-stock-qty"
          type="number"
          x-model.number="quantity"
          min="1"
          class="form-input w-32"
        >
      </div>

      <!-- Success / error messages -->
      <div x-show="submitSuccess" class="ta-alert ta-alert-success mb-4" role="status">
        <?php echo ui_text($lang === 'ja' ? '登録しました' : 'Registered successfully'); ?>
      </div>
      <div x-show="submitError" x-text="submitError" class="ta-alert ta-alert-danger mb-4" role="alert"></div>

      <!-- Submit -->
      <button
        type="button"
        @click="submitStock()"
        class="btn-primary w-full"
        :disabled="submitting || !item"
      >
        <span x-show="!submitting"><?php echo ui_text($submitLabel); ?></span>
        <span x-show="submitting" class="flex items-center justify-center gap-2">
          <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          <?php echo ui_text($lang === 'ja' ? '登録中…' : 'Registering…'); ?>
        </span>
      </button>
    </div>
  </div>

</div>

<?php
};
?>
