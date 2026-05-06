<?php $this->title = 'バーコードから商品登録'; ?>
<?php $this->content = function ($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<div
  x-data="{
    barcodeInput: '',
    lookupResult: null,
    lookupError: null,
    loading: false,
    async lookup() {
      if (!this.barcodeInput.trim()) return;
      this.loading = true;
      this.lookupResult = null;
      this.lookupError = null;
      try {
        const res = await fetch('./api/v1/barcode/' + encodeURIComponent(this.barcodeInput.trim()));
        const data = await res.json();
        if (data.item || data.code) {
          this.lookupResult = data;
          if (!data.item) { this.lookupError = null; }
        } else {
          this.lookupError = '<?php echo $lang === 'ja' ? 'バーコードが見つかりません' : 'Barcode not found'; ?>';
        }
      } catch(e) {
        this.lookupError = '<?php echo $lang === 'ja' ? '検索エラーが発生しました' : 'Lookup error occurred'; ?>';
      } finally { this.loading = false; }
    }
  }"
>
  <div class="row g-3">

    <!-- Step 1: Barcode entry -->
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title">
            <span class="badge bg-primary rounded-circle me-2">1</span>
            <?php echo $lang === 'ja' ? 'バーコードを入力・スキャン' : 'Enter or Scan Barcode'; ?>
          </h3>
        </div>
        <div class="card-body">
          <p class="small text-muted mb-3">
            <?php echo $lang === 'ja'
              ? 'バーコードシートから印刷したバーコードをスキャンするか、番号を直接入力してください。'
              : 'Scan the barcode from your printed sheet, or enter the number directly.'; ?>
          </p>

          <div class="mb-3">
            <label for="barcode-input" class="form-label"><?php echo $lang === 'ja' ? 'バーコード番号' : 'Barcode Number'; ?></label>
            <?php
            $inputId     = 'barcode-input';
            $buttonLabel = __('ui.scanner.open', [], null, 'Scan Barcode / QR');
            $uniqueId    = 'frombarcode';
            include __DIR__ . '/../../root/template/_components/barcodeScanner.php';
            ?>
            <div class="input-group mt-2">
              <span class="input-group-text">
                <i class="bi bi-qr-code" aria-hidden="true"></i>
              </span>
              <input
                id="barcode-input"
                x-model="barcodeInput"
                type="text"
                class="form-control"
                placeholder="<?php echo $lang === 'ja' ? '例: BC00001' : 'e.g. BC00001'; ?>"
                @keydown.enter.prevent="lookup()"
                autocomplete="off"
                aria-label="<?php echo $lang === 'ja' ? 'バーコード番号' : 'Barcode number'; ?>"
              >
              <button type="button" @click="lookup()" class="btn btn-primary" :disabled="loading">
                <span x-show="!loading"><i class="bi bi-search me-1" aria-hidden="true"></i><?php echo $lang === 'ja' ? '検索' : 'Search'; ?></span>
                <span x-show="loading" class="d-flex align-items-center gap-2">
                  <span class="spinner-border spinner-border-sm" role="status" aria-label="<?php echo $lang === 'ja' ? '検索中' : 'Searching'; ?>"></span>
                  <?php echo $lang === 'ja' ? '検索中...' : 'Searching...'; ?>
                </span>
              </button>
            </div>
            <div x-show="lookupError" x-text="lookupError" class="mt-1 small text-danger" role="alert" aria-live="polite"></div>
          </div>

          <!-- Lookup result: already registered -->
          <div x-show="lookupResult && lookupResult.item && lookupResult.item.id" class="alert alert-success d-flex align-items-start gap-2 mt-3" role="status">
            <i class="bi bi-check-circle fs-5 flex-shrink-0" aria-hidden="true"></i>
            <div>
              <div class="fw-medium"><?php echo $lang === 'ja' ? '商品登録済み' : 'Already Registered'; ?></div>
              <div class="small" x-text="lookupResult && lookupResult.item ? lookupResult.item.name : ''"></div>
              <a :href="'./item/start/item/' + (lookupResult && lookupResult.item ? lookupResult.item.id : '')" class="small">
                <?php echo $lang === 'ja' ? '商品詳細を見る →' : 'View product details →'; ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 2: Register product info -->
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title">
            <span class="badge bg-primary rounded-circle me-2">2</span>
            <?php echo $lang === 'ja' ? '商品情報を入力' : 'Enter Product Info'; ?>
          </h3>
        </div>
        <div class="card-body">
          <form method="post" action="./item/registerFromBarcode/" novalidate>
            <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
            <input type="hidden" name="barcodeId" :value="barcodeInput">

            <div class="mb-3">
              <label for="fb-barcode" class="form-label"><?php echo $lang === 'ja' ? 'バーコード番号' : 'Barcode'; ?></label>
              <input id="fb-barcode" type="text" :value="barcodeInput" class="form-control bg-light" readonly aria-readonly="true">
            </div>

            <div class="mb-3">
              <label for="fb-name" class="form-label"><?php echo $lang === 'ja' ? '商品名' : 'Product Name'; ?> <span class="text-danger">*</span></label>
              <input id="fb-name" type="text" name="itemName" class="form-control" maxlength="50" required aria-required="true" placeholder="<?php echo $lang === 'ja' ? '商品名を入力' : 'Product name'; ?>">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label for="fb-color" class="form-label"><?php echo $lang === 'ja' ? '色' : 'Color'; ?> <span class="text-danger">*</span></label>
                <input id="fb-color" type="text" name="colorName" class="form-control" required aria-required="true" placeholder="<?php echo $lang === 'ja' ? '赤, 青' : 'Red, Blue'; ?>">
              </div>
              <div class="col-6">
                <label for="fb-size" class="form-label"><?php echo $lang === 'ja' ? 'サイズ' : 'Size'; ?> <span class="text-danger">*</span></label>
                <input id="fb-size" type="text" name="sizeName" class="form-control" required aria-required="true" placeholder="S, M, L">
              </div>
            </div>

            <div class="mb-3">
              <label for="fb-price" class="form-label"><?php echo $lang === 'ja' ? '価格' : 'Price'; ?></label>
              <div class="input-group">
                <span class="input-group-text">¥</span>
                <input id="fb-price" type="text" name="price" pattern="^[0-9,]+$" maxlength="11" class="form-control" placeholder="0">
              </div>
            </div>

            <div class="mb-4">
              <label for="fb-category" class="form-label"><?php echo $lang === 'ja' ? '分類' : 'Category'; ?></label>
              <div id="fb-category-selector" class="border rounded p-3 small text-muted">
                <?php echo $lang === 'ja' ? '（分類選択は通常の商品登録画面と同様に設定できます）' : '(Category selection available on the full registration form)'; ?>
              </div>
              <input type="hidden" name="categoryId" id="fb-categoryId" value="">
            </div>

            <button type="submit" class="btn btn-primary w-100" :disabled="!barcodeInput">
              <i class="bi bi-plus me-2" aria-hidden="true"></i>
              <?php echo $lang === 'ja' ? '商品情報を登録する' : 'Register Product'; ?>
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<?php }; ?>
