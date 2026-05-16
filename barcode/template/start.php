<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $csrfToken = \saso\util\CSRFtoken::current();
    $placeholder = __('ui.barcode.input_placeholder', [], null, 'Input barcode');
    $labelDisplay = __('ui.common.display', [], null, 'Display');
    $labelViewItem = $lang === 'ja' ? '商品詳細を見る →' : 'View item details →';
    $labelNotFound = $lang === 'ja' ? 'バーコードが見つかりません' : 'Barcode not found';
    $labelUnlinked  = $lang === 'ja' ? 'このバーコードはまだ商品に紐付けられていません' : 'This barcode is not yet linked to any item';
    $labelError     = $lang === 'ja' ? '検索エラーが発生しました' : 'Lookup error occurred';
    $labelInvalid   = $lang === 'ja' ? '有効な12桁のバーコードを入力してください' : 'Please enter a valid 12-digit barcode';
    $labelRegistered = $lang === 'ja' ? '商品登録済み' : 'Item found';
    $labelCode      = $lang === 'ja' ? '商品コード' : 'Item code';
    $labelColor     = $lang === 'ja' ? '色コード' : 'Color';
    $labelSize      = $lang === 'ja' ? 'サイズコード' : 'Size';
    $labelRegister  = $lang === 'ja' ? '商品を登録する' : 'Register item';
    $labelRegTitle  = $lang === 'ja' ? '新しい商品を登録' : 'Register new item';
    $labelRegName   = $lang === 'ja' ? '商品名' : 'Product name';
    $labelRegColor  = $lang === 'ja' ? '色' : 'Color';
    $labelRegSize   = $lang === 'ja' ? 'サイズ' : 'Size';
    $labelRegPrice  = $lang === 'ja' ? '価格' : 'Price';
    $labelRegSubmit = $lang === 'ja' ? '登録する' : 'Register';
    $labelRegCancel = $lang === 'ja' ? 'キャンセル' : 'Cancel';
    $labelRegError  = $lang === 'ja' ? '登録に失敗しました' : 'Registration failed';
    $labelRegRequired = $lang === 'ja' ? '商品名・色・サイズは必須です' : 'Name, color, and size are required';
    $labelScanDetected = $lang === 'ja' ? 'バーコードを検知しました' : 'Barcode detected';
?>

<div class="flex justify-center mb-8">
  <div
    x-data="sasoBarcodeSearch"
    data-csrf="<?php echo ui_attr($csrfToken); ?>"
    data-label-not-found="<?php echo ui_attr($labelNotFound); ?>"
    data-label-invalid="<?php echo ui_attr($labelInvalid); ?>"
    data-label-reg-error="<?php echo ui_attr($labelRegError); ?>"
    data-label-reg-required="<?php echo ui_attr($labelRegRequired); ?>"
    @keydown.window="onWindowKey($event)"
    class="mb-6"
  >
  <div class="card">
    <div class="card-body">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative grow">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            <?php ui('iconHeroicon', ['name' => 'qr', 'class' => 'h-5 w-5']); ?>
          </div>
          <input
            id="barcodeInput"
            x-model="code"
            type="text"
            class="form-input pl-11"
            placeholder="<?php echo ui_attr($placeholder); ?>"
            @keydown.enter.prevent="search()"
            autocomplete="off"
            inputmode="text"
          >
        </div>
        <button
          type="button"
          id="barcodeSubmit"
          class="btn btn-primary shrink-0 flex items-center justify-center gap-2"
          @click="search()"
          :disabled="loading"
        >
          <span x-show="loading" aria-hidden="true">
            <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
          </span>
          <span x-show="!loading" aria-hidden="true">
            <?php ui('iconHeroicon', ['name' => 'search', 'class' => 'h-4 w-4 shrink-0']); ?>
          </span>
          <span><?php echo ui_text($labelDisplay); ?></span>
        </button>
      </div>

      <!-- error -->
      <div x-show="error" x-cloak class="mt-3 ta-alert ta-alert-danger" role="alert" aria-live="polite">
        <?php ui('iconHeroicon', ['name' => 'x-circle', 'class' => 'h-5 w-5 shrink-0']); ?>
        <span x-text="error"></span>
      </div>

      <!-- PND result: linked -->
      <div x-show="result && result.type === 'pnd'" x-cloak class="mt-3 ta-alert ta-alert-success">
        <?php ui('iconHeroicon', ['name' => 'check-square', 'class' => 'h-5 w-5 shrink-0']); ?>
        <div>
          <p class="font-medium"><?php echo ui_text($labelRegistered); ?></p>
          <p class="text-sm" x-text="result && result.name"></p>
          <a :href="itemUrl" class="text-sm underline"><?php echo ui_text($labelViewItem); ?></a>
        </div>
      </div>

      <!-- PND unlinked: inline registration form -->
      <div x-show="showRegModal" x-cloak class="mt-4 rounded-xl border p-4" style="background:var(--saso-card-sub, #f8f9fa);border-color:var(--saso-card-bdr)">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="flex items-center gap-2 font-semibold text-sm" style="color:var(--saso-text)">
            <?php ui('iconHeroicon', ['name' => 'plus-circle', 'class' => 'h-4 w-4 text-blue-600']); ?>
            <?php echo ui_text($labelRegTitle); ?>
            <span class="font-mono text-xs text-gray-500" x-text="reg.barcodeId"></span>
          </h3>
          <button type="button" @click="showRegModal = false" class="text-gray-400 hover:text-gray-600">
            <?php ui('iconHeroicon', ['name' => 'x-circle', 'class' => 'h-4 w-4']); ?>
          </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="form-label text-xs"><?php echo ui_text($labelRegName); ?> <span class="text-red-500">*</span></label>
            <input
              x-model="reg.itemName"
              type="text"
              maxlength="50"
              class="form-input"
              placeholder="<?php echo $lang === 'ja' ? '商品名を入力' : 'Enter product name'; ?>"
              @keydown.enter.prevent="submitReg()"
            >
          </div>
          <div>
            <label class="form-label text-xs"><?php echo ui_text($labelRegColor); ?> <span class="text-red-500">*</span></label>
            <input
              x-model="reg.colorName"
              type="text"
              class="form-input"
              placeholder="<?php echo $lang === 'ja' ? '赤, 青' : 'Red, Blue'; ?>"
              @keydown.enter.prevent="submitReg()"
            >
          </div>
          <div>
            <label class="form-label text-xs"><?php echo ui_text($labelRegSize); ?> <span class="text-red-500">*</span></label>
            <input
              x-model="reg.sizeName"
              type="text"
              class="form-input"
              placeholder="S, M, L"
              @keydown.enter.prevent="submitReg()"
            >
          </div>
          <div>
            <label class="form-label text-xs"><?php echo ui_text($labelRegPrice); ?></label>
            <div class="relative">
              <span class="absolute left-3 top-2.5 text-xs text-gray-500">¥</span>
              <input
                x-model="reg.price"
                type="text"
                pattern="^[0-9,]*$"
                maxlength="11"
                class="form-input pl-6"
                placeholder="0"
                @keydown.enter.prevent="submitReg()"
              >
            </div>
          </div>
        </div>

        <div x-show="regError" x-cloak class="mt-2 text-xs text-red-600" x-text="regError" role="alert" aria-live="polite"></div>

        <div class="mt-3 flex gap-2">
          <button
            type="button"
            @click="submitReg()"
            class="btn btn-primary btn-sm flex-1"
            :disabled="regLoading"
          >
            <span x-show="!regLoading"><?php echo ui_text($labelRegSubmit); ?></span>
            <span x-show="regLoading" class="flex items-center justify-center gap-1">
              <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
            </span>
          </button>
          <button
            type="button"
            @click="showRegModal = false"
            class="btn btn-secondary btn-sm"
          ><?php echo ui_text($labelRegCancel); ?></button>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<?php }; ?>
