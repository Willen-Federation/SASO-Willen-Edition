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
    x-data="{
      code: '',
      error: null,
      loading: false,

    showRegModal: false,
    reg: { barcodeId: '', itemName: '', colorName: '', sizeName: '', price: '' },
    regLoading: false,
    regError: null,
    csrfToken: <?php echo json_encode($csrfToken); ?>,

    _buf: '',
    _lastTime: 0,
    _timer: null,
    SCAN_GAP_MS: 50,
    MIN_SCAN_CHARS: 8,

    onWindowKey(e) {
      const el = document.activeElement;
      const tag = el ? el.tagName : '';
      const isBarcodeInput = el && el.id === 'barcodeInput';
      const isOtherInput = !isBarcodeInput && (
        tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' ||
        (el && el.isContentEditable)
      );
      if (isOtherInput) return;

      const now = performance.now();
      const gap = now - this._lastTime;
      this._lastTime = now;

      if (e.key === 'Enter') {
        if (this._buf.length >= this.MIN_SCAN_CHARS) {
          e.preventDefault();
          this.code = this._buf;
          this._buf = '';
          if (this._timer) { clearTimeout(this._timer); this._timer = null; }
          this.$nextTick(() => this.search());
        } else {
          this._buf = '';
        }
        return;
      }

      if (e.key.length !== 1) return;

      if (this._buf.length > 0 && gap > this.SCAN_GAP_MS * 5) {
        this._buf = '';
      }

      this._buf += e.key;

      if (this._timer) clearTimeout(this._timer);
      if (this._buf.length >= this.MIN_SCAN_CHARS) {
        this._timer = setTimeout(() => {
          if (this._buf.length >= this.MIN_SCAN_CHARS) {
            this.code = this._buf;
            this._buf = '';
            this.search();
          }
          this._timer = null;
        }, 150);
      }
    },

    isPnd()    { return /^PND\d{9}$/.test(this.code.trim()); },
    isLegacy() { return /^\d{12}$/.test(this.code.trim()); },

    async search() {
      const raw = this.code.trim();
      this.result  = null;
      this.error   = null;
      this.itemUrl = null;
      this.showRegModal = false;
      if (!raw) return;

      if (this.isPnd()) {
        this.loading = true;
        try {
          const res  = await fetch('./api/v1/barcode/' + encodeURIComponent(raw));
          const data = await res.json();
          if (!res.ok || !data) {
            this.error = <?php echo json_encode($labelNotFound); ?>;
          } else if (data.item && data.item.id) {
            this.result  = { type: 'pnd', name: data.item.name, id: data.item.id };
            this.itemUrl = './item/start/item/' + data.item.id + '/';
          } else {
            this.reg.barcodeId = raw;
            this.reg.itemName = '';
            this.reg.colorName = '';
            this.reg.sizeName = '';
            this.reg.price = '';
            this.regError = null;
            this.showRegModal = true;
          }
        } else if (this.isLegacy()) {
          const item  = raw.slice(0, 8);
          const color = raw.slice(8, 10);
          const size  = raw.slice(10, 12);
          window.location.href = './item/start/item/' + item + '/color/' + color + '/size/' + size + '/action/shelf';
        } else {
          this.error = <?php echo json_encode($labelInvalid); ?>;
        }
      }
    },

    async submitReg() {
      if (!this.reg.itemName.trim() || !this.reg.colorName.trim() || !this.reg.sizeName.trim()) {
        this.regError = <?php echo json_encode($labelRegRequired); ?>;
        return;
      }
      this.regLoading = true;
      this.regError = null;
      try {
        const form = new FormData();
        form.append('csrftoken', this.csrfToken);
        form.append('barcodeId', this.reg.barcodeId);
        form.append('itemName', this.reg.itemName.trim());
        form.append('colorName', this.reg.colorName.trim());
        form.append('sizeName', this.reg.sizeName.trim());
        form.append('price', this.reg.price);
        const res = await fetch('./item/registerFromBarcode/', {
          method: 'POST',
          body: form,
          redirect: 'follow'
        });
        if (res.ok && res.url && res.url.includes('/item/')) {
          window.location.href = res.url;
        } else {
          this.regError = <?php echo json_encode($labelRegError); ?>;
        }
      } catch (e) {
        this.regError = <?php echo json_encode($labelRegError); ?>;
      } finally {
        this.regLoading = false;
      }
    }
  }"
  @keydown.window="onWindowKey($event)"
  class="mb-6"
>
  <div class="card">
    <div class="card-body">
      <div class="flex items-center gap-3">
        <div class="relative grow max-w-xs">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            <?php ui('iconHeroicon', ['name' => 'qr', 'class' => 'h-5 w-5']); ?>
          </div>
          <button
            type="button"
            id="barcodeSubmit"
            class="btn btn-primary shrink-0 flex items-center gap-2"
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
            <?php echo ui_text($labelSubmit); ?>
          </button>
        </div>
        <button
          type="button"
          id="barcodeSubmit"
          class="btn btn-success"
          @click="search()"
          :disabled="loading"
        >
          <span x-show="!loading"><?php echo ui_text($labelDisplay); ?></span>
          <span x-show="loading" class="flex items-center gap-1">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
          </span>
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

        <div x-show="error" x-cloak class="mt-3 ta-alert ta-alert-danger" role="alert" aria-live="polite">
          <?php ui('iconHeroicon', ['name' => 'x-circle', 'class' => 'h-5 w-5 shrink-0']); ?>
          <span x-text="error"></span>
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

<?php }; ?>
