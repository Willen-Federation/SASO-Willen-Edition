<?php $this->title = 'バーコードから商品登録'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item"><a href="./barcode/sheet/"><?php echo $lang === 'ja' ? 'バーコードシート印刷' : 'Print Barcode Sheets'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'バーコードから商品登録' : 'Register from Barcode'; ?></li>
  </ol>
</nav>

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
        if (data.item) { this.lookupResult = data; }
        else { this.lookupError = '<?php echo $lang === 'ja' ? 'バーコードが見つかりません' : 'Barcode not found'; ?>'; }
      } catch(e) {
        this.lookupError = '<?php echo $lang === 'ja' ? '検索エラーが発生しました' : 'Lookup error occurred'; ?>';
      } finally { this.loading = false; }
    }
  }"
>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <!-- Step 1: Barcode entry -->
    <div class="card">
      <div class="card-header">
        <h2 class="font-semibold text-black dark:text-white">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white text-xs mr-2">1</span>
          <?php echo $lang === 'ja' ? 'バーコードを入力・スキャン' : 'Enter or Scan Barcode'; ?>
        </h2>
      </div>
      <div class="card-body">
        <p class="mb-4 text-sm text-body dark:text-bodydark">
          <?php echo $lang === 'ja'
            ? 'バーコードシートから印刷したバーコードをスキャンするか、番号を直接入力してください。'
            : 'Scan the barcode from your printed sheet, or enter the number directly.'; ?>
        </p>
        <div class="mb-4">
          <label for="barcode-input" class="form-label"><?php echo $lang === 'ja' ? 'バーコード番号' : 'Barcode Number'; ?></label>
          <div class="flex gap-2">
            <div class="relative flex-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3.5 h-5 w-5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
              <input
                id="barcode-input"
                x-model="barcodeInput"
                type="text"
                class="form-input pl-11"
                placeholder="<?php echo $lang === 'ja' ? '例: BC00001' : 'e.g. BC00001'; ?>"
                @keydown.enter.prevent="lookup()"
                autocomplete="off"
                aria-label="<?php echo $lang === 'ja' ? 'バーコード番号' : 'Barcode number'; ?>"
              >
            </div>
            <button type="button" @click="lookup()" class="btn-primary px-6" :disabled="loading">
              <span x-show="!loading"><?php echo $lang === 'ja' ? '検索' : 'Search'; ?></span>
              <span x-show="loading">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              </span>
            </button>
          </div>
          <p x-show="lookupError" x-text="lookupError" class="mt-2 text-sm text-danger" role="alert" aria-live="polite"></p>
        </div>

        <!-- Lookup result: already registered -->
        <div x-show="lookupResult?.item?.id" class="alert alert-success mt-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <div>
            <p class="font-medium"><?php echo $lang === 'ja' ? '商品登録済み' : 'Already Registered'; ?></p>
            <p class="text-sm" x-text="lookupResult?.item?.name"></p>
            <a :href="'./item/start/item/' + lookupResult?.item?.id" class="text-sm underline">
              <?php echo $lang === 'ja' ? '商品詳細を見る →' : 'View product details →'; ?>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 2: Register product info -->
    <div class="card">
      <div class="card-header">
        <h2 class="font-semibold text-black dark:text-white">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white text-xs mr-2">2</span>
          <?php echo $lang === 'ja' ? '商品情報を入力' : 'Enter Product Info'; ?>
        </h2>
      </div>
      <div class="card-body">
        <form method="post" action="./item/registerFromBarcode/" novalidate>
          <input type="hidden" name="barcodeId" :value="barcodeInput">

          <div class="mb-4">
            <label for="fb-barcode" class="form-label"><?php echo $lang === 'ja' ? 'バーコード番号' : 'Barcode'; ?></label>
            <input id="fb-barcode" type="text" :value="barcodeInput" class="form-input bg-gray-2 dark:bg-meta-4" readonly aria-readonly="true">
          </div>

          <div class="mb-4">
            <label for="fb-name" class="form-label"><?php echo $lang === 'ja' ? '商品名' : 'Product Name'; ?> <span class="text-danger">*</span></label>
            <input id="fb-name" type="text" name="itemName" class="form-input" maxlength="50" required aria-required="true" placeholder="<?php echo $lang === 'ja' ? '商品名を入力' : 'Product name'; ?>">
          </div>

          <div class="mb-4 grid grid-cols-2 gap-3">
            <div>
              <label for="fb-color" class="form-label"><?php echo $lang === 'ja' ? '色' : 'Color'; ?> <span class="text-danger">*</span></label>
              <input id="fb-color" type="text" name="colorName" class="form-input" required aria-required="true" placeholder="<?php echo $lang === 'ja' ? '赤, 青' : 'Red, Blue'; ?>">
            </div>
            <div>
              <label for="fb-size" class="form-label"><?php echo $lang === 'ja' ? 'サイズ' : 'Size'; ?> <span class="text-danger">*</span></label>
              <input id="fb-size" type="text" name="sizeName" class="form-input" required aria-required="true" placeholder="S, M, L">
            </div>
          </div>

          <div class="mb-4">
            <label for="fb-price" class="form-label"><?php echo $lang === 'ja' ? '価格' : 'Price'; ?></label>
            <div class="relative">
              <span class="absolute left-4 top-3.5 text-body">¥</span>
              <input id="fb-price" type="text" name="price" pattern="^[0-9,]+$" maxlength="11" class="form-input pl-8" placeholder="0">
            </div>
          </div>

          <div class="mb-5">
            <label for="fb-category" class="form-label"><?php echo $lang === 'ja' ? '分類' : 'Category'; ?></label>
            <div id="fb-category-selector" class="rounded border border-stroke p-3 dark:border-strokedark text-sm text-body">
              <?php echo $lang === 'ja' ? '（分類選択は通常の商品登録画面と同様に設定できます）' : '(Category selection available on the full registration form)'; ?>
            </div>
            <input type="hidden" name="categoryId" id="fb-categoryId" value="">
          </div>

          <button type="submit" class="btn-primary w-full" :disabled="!barcodeInput">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo $lang === 'ja' ? '商品情報を登録する' : 'Register Product'; ?>
          </button>
        </form>
      </div>
    </div>

  </div>

</div>

<?php }; ?>
