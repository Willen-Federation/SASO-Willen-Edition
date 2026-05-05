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
    csrfToken: <?php echo json_encode(\saso\util\CSRFtoken::current(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,

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
      const id = this.item ? this.item.id : '';
      if (this.mode === 'stock')     return './item/stock/item/' + id + '/';
      if (this.mode === 'shipment')  return './item/shipment/item/' + id + '/';
      if (this.mode === 'inventory') return './item/inventory/item/' + id + '/';
      return './item/stock/item/' + id + '/';
    },

    async submitStock() {
      if (!this.item) return;
      this.submitting   = true;
      this.submitSuccess = false;
      this.submitError  = null;
      try {
        const form = new FormData();
        form.append('kind',      this.mode);
        form.append('amount',    this.quantity);
        form.append('csrftoken', this.csrfToken);
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

  <div class="mb-4">
    <h1 class="h4 fw-semibold"><?php echo ui_text($title); ?></h1>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2" role="tablist"
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

  <div class="card mb-4">
    <div class="card-header">
      <h2 class="card-title">
        <?php echo ui_text($lang === 'ja' ? 'バーコードをスキャン' : 'Scan Barcode'); ?>
      </h2>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <label for="scan-stock-barcode" class="form-label">
            <?php echo ui_text($lang === 'ja' ? 'バーコード' : 'Barcode'); ?>
          </label>
          <div class="input-group">
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
              class="form-control"
              placeholder="<?php echo ui_attr($lang === 'ja' ? 'バーコード番号' : 'Barcode number'); ?>"
              @keydown.enter.prevent="onBarcodeDetected(scannedCode)"
              autocomplete="off"
            >
            <button
              type="button"
              @click="onBarcodeDetected(scannedCode)"
              class="btn btn-primary"
              :disabled="loading || !scannedCode.trim()"
            >
              <span x-show="!loading">
                <i class="ti ti-search me-1" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? '検索' : 'Find'); ?>
              </span>
              <span x-show="loading" class="d-flex align-items-center gap-1">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              </span>
            </button>
          </div>
          <div x-show="itemError" x-text="itemError" class="mt-1 small text-danger" role="alert"></div>
        </div>
      </div>
    </div>
  </div>

  <div x-show="item" class="card mb-4">
    <div class="card-body">

      <div class="mb-4 d-flex align-items-start gap-3">
        <div>
          <p class="fw-semibold mb-1" x-text="item && item.name"></p>
          <p class="small text-muted mb-0">
            ID: <span x-text="item && item.id"></span>
          </p>
        </div>
      </div>

      <div class="mb-4">
        <label for="scan-stock-qty" class="form-label">
          <?php echo ui_text($lang === 'ja' ? '数量' : 'Quantity'); ?>
        </label>
        <input
          id="scan-stock-qty"
          type="number"
          x-model.number="quantity"
          min="1"
          class="form-control"
          style="width:8rem;"
        >
      </div>

      <div x-show="submitSuccess" class="alert alert-success mb-4" role="status">
        <?php echo ui_text($lang === 'ja' ? '登録しました' : 'Registered successfully'); ?>
      </div>
      <div x-show="submitError" x-text="submitError" class="alert alert-danger mb-4" role="alert"></div>

      <button
        type="button"
        @click="submitStock()"
        class="btn btn-primary w-100"
        :disabled="submitting || !item"
      >
        <span x-show="!submitting"><?php echo ui_text($submitLabel); ?></span>
        <span x-show="submitting" class="d-flex align-items-center justify-content-center gap-2">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          <?php echo ui_text($lang === 'ja' ? '登録中…' : 'Registering…'); ?>
        </span>
      </button>
    </div>
  </div>

</div>

<?php
};
?>
