<?php $this->title = '商品登録'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? '商品登録' : 'Register Product'; ?></li>
  </ol>
</nav>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
  <!-- Main form -->
  <div class="lg:col-span-2">
    <div class="card">
      <div class="card-header">
        <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '基本情報' : 'Basic Info'; ?></h2>
      </div>
      <div class="card-body">
        <form method="post" action="./item/add/" novalidate>

          <!-- 商品名 -->
          <div class="mb-5">
            <label for="item-name" class="form-label">
              <?php echo $lang === 'ja' ? '商品名' : 'Product Name'; ?> <span class="text-danger">*</span>
              <span class="ml-2 text-xs text-body">(<?php echo $lang === 'ja' ? '50文字以内' : 'max 50 chars'; ?>)</span>
            </label>
            <input
              id="item-name"
              type="text"
              name="itemName"
              class="form-input"
              maxlength="50"
              required
              aria-required="true"
              placeholder="<?php echo $lang === 'ja' ? '商品名を入力してください' : 'Enter product name'; ?>"
            >
          </div>

          <!-- 分類 -->
          <div class="mb-5">
            <label class="form-label"><?php echo $lang === 'ja' ? '分類' : 'Category'; ?></label>
            <div id="category" class="rounded border border-stroke bg-transparent p-4 dark:border-strokedark">
              <div id="appendingParentInputs"></div>
              <button type="button" id="appendingParent" class="btn-secondary btn-sm text-xs px-3 py-1.5 mb-3">
                + <?php echo $lang === 'ja' ? '親分類を追加' : 'Add Parent'; ?>
              </button>
              <div id="categoriesRoot"></div>
              <p class="mt-3 text-sm">
                <?php echo $lang === 'ja' ? '選択中：' : 'Selected: '; ?>
                <span class="categoryPath categoryPathChangable font-medium text-primary"></span>
                <button type="button" class="hidden ml-2 text-xs text-danger underline" id="deselectCategory">
                  <?php echo $lang === 'ja' ? '解除' : 'Clear'; ?>
                </button>
              </p>
            </div>
            <input type="hidden" name="categoryId" id="categoryId" value="">
          </div>

          <!-- 価格 -->
          <div class="mb-5">
            <label for="item-price" class="form-label">
              <?php echo $lang === 'ja' ? '価格' : 'Price'; ?>
              <span class="ml-2 text-xs text-body">(<?php echo $lang === 'ja' ? '9桁まで' : 'up to 9 digits'; ?>)</span>
            </label>
            <div class="relative">
              <span class="absolute left-4 top-3.5 text-body">¥</span>
              <input
                id="item-price"
                type="text"
                name="price"
                pattern="^[0-9,]+$"
                maxlength="11"
                class="form-input pl-8"
                placeholder="0"
                aria-describedby="price-hint"
              >
            </div>
          </div>

          <!-- 色 / サイズ -->
          <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label for="item-color" class="form-label">
                <?php echo $lang === 'ja' ? '色' : 'Color'; ?> <span class="text-danger">*</span>
              </label>
              <input
                id="item-color"
                type="text"
                name="colorName"
                class="form-input"
                required
                aria-required="true"
                placeholder="<?php echo $lang === 'ja' ? '赤, 青, 緑（カンマ区切り）' : 'Red, Blue (comma separated)'; ?>"
                aria-describedby="color-hint"
              >
              <p id="color-hint" class="mt-1 text-xs text-body dark:text-bodydark"><?php echo $lang === 'ja' ? '複数の場合は半角カンマ(,)で区切り' : 'Multiple values: comma separated'; ?></p>
            </div>
            <div>
              <label for="item-size" class="form-label">
                <?php echo $lang === 'ja' ? 'サイズ' : 'Size'; ?> <span class="text-danger">*</span>
              </label>
              <input
                id="item-size"
                type="text"
                name="sizeName"
                class="form-input"
                required
                aria-required="true"
                placeholder="<?php echo $lang === 'ja' ? 'S, M, L（カンマ区切り）' : 'S, M, L (comma separated)'; ?>"
                aria-describedby="size-hint"
              >
              <p id="size-hint" class="mt-1 text-xs text-body dark:text-bodydark"><?php echo $lang === 'ja' ? '色数 × サイズ数 ≤ 100' : 'Colors × Sizes ≤ 100'; ?></p>
            </div>
          </div>

          <!-- 梱包 -->
          <div class="mb-6">
            <span class="form-label block mb-3"><?php echo $lang === 'ja' ? '梱包材' : 'Packaging'; ?></span>
            <div class="flex flex-col gap-3">
              <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" name="pla" value="1" class="h-5 w-5 rounded border-stroke text-primary focus:ring-primary dark:border-strokedark">
                  <span class="text-sm font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? 'プラスチック' : 'Plastic'; ?></span>
                </label>
                <input
                  type="text"
                  name="plaNote"
                  maxlength="50"
                  class="form-input flex-1"
                  placeholder="<?php echo $lang === 'ja' ? 'メモ（任意）' : 'Note (optional)'; ?>"
                >
              </div>
              <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" name="paper" value="1" class="h-5 w-5 rounded border-stroke text-primary focus:ring-primary dark:border-strokedark">
                  <span class="text-sm font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? '紙' : 'Paper'; ?></span>
                </label>
                <input
                  type="text"
                  name="paperNote"
                  maxlength="50"
                  class="form-input flex-1"
                  placeholder="<?php echo $lang === 'ja' ? 'メモ（任意）' : 'Note (optional)'; ?>"
                >
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary px-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?php echo $lang === 'ja' ? '登録する' : 'Register'; ?>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Side help -->
  <div class="space-y-4">
    <div class="card">
      <div class="card-header">
        <h3 class="font-semibold text-black dark:text-white text-sm"><?php echo $lang === 'ja' ? '入力のポイント' : 'Tips'; ?></h3>
      </div>
      <div class="card-body text-sm text-body dark:text-bodydark space-y-3">
        <div class="flex gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <p><?php echo $lang === 'ja' ? '色・サイズは半角カンマ(,)で複数入力できます' : 'Multiple colors/sizes: use comma (,)'; ?></p>
        </div>
        <div class="flex gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <p><?php echo $lang === 'ja' ? '色数×サイズ数は100以下にしてください' : 'Colors × Sizes must be ≤ 100'; ?></p>
        </div>
        <div class="flex gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-meta-3 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <p><?php echo $lang === 'ja' ? '各色・サイズは50文字まで' : 'Each color/size: max 50 chars'; ?></p>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3 class="font-semibold text-black dark:text-white text-sm"><?php echo $lang === 'ja' ? 'バーコードから登録' : 'Register from Barcode'; ?></h3>
      </div>
      <div class="card-body text-sm text-body dark:text-bodydark">
        <p class="mb-3"><?php echo $lang === 'ja' ? '先にバーコードシートを印刷して、後から商品情報を紐づけることもできます。' : 'Print barcode sheets first, then attach product info later.'; ?></p>
        <a href="./barcode/sheet/" class="btn-secondary btn-sm text-xs w-full text-center">
          <?php echo $lang === 'ja' ? 'バーコードシートを印刷' : 'Print Barcode Sheet'; ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
