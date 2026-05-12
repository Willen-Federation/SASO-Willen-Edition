<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
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
?>

<div
  x-data="{
    code: '',
    result: null,
    error: null,
    loading: false,
    itemUrl: null,

    isPnd()    { return /^PND\d{9}$/.test(this.code.trim()); },
    isLegacy() { return /^\d{12}$/.test(this.code.trim()); },

    async search() {
      const raw = this.code.trim();
      this.result  = null;
      this.error   = null;
      this.itemUrl = null;
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
            this.error = <?php echo json_encode($labelUnlinked); ?>;
          }
        } catch (e) {
          this.error = <?php echo json_encode($labelError); ?>;
        } finally {
          this.loading = false;
        }
      } else if (this.isLegacy()) {
        const item  = raw.slice(0, 8);
        const color = raw.slice(8, 10);
        const size  = raw.slice(10, 12);
        this.result  = { type: 'legacy', item, color, size };
        this.itemUrl = './item/start/item/' + item + '/color/' + color + '/size/' + size + '/action/shelf';
      } else {
        this.error = <?php echo json_encode($labelInvalid); ?>;
      }
    }
  }"
  class="mb-6"
>
  <div class="card">
    <div class="card-body">
      <div class="flex items-center gap-3">
        <div class="relative grow max-w-xs">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            <?php ui('iconHeroicon', ['name' => 'qr', 'class' => 'h-5 w-5']); ?>
          </div>
          <input
            id="barcodeInput"
            x-model="code"
            type="text"
            maxlength="12"
            class="form-input pl-10"
            placeholder="<?php echo ui_attr($placeholder); ?>"
            @keydown.enter.prevent="search()"
            autocomplete="off"
          >
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

      <!-- PND result -->
      <div x-show="result && result.type === 'pnd'" x-cloak class="mt-3 ta-alert ta-alert-success">
        <?php ui('iconHeroicon', ['name' => 'check-square', 'class' => 'h-5 w-5 shrink-0']); ?>
        <div>
          <p class="font-medium"><?php echo ui_text($labelRegistered); ?></p>
          <p class="text-sm" x-text="result && result.name"></p>
          <a :href="itemUrl" class="text-sm underline"><?php echo ui_text($labelViewItem); ?></a>
        </div>
      </div>

      <!-- Legacy barcode result -->
      <div x-show="result && result.type === 'legacy'" x-cloak class="mt-3 ta-alert ta-alert-success">
        <?php ui('iconHeroicon', ['name' => 'check-square', 'class' => 'h-5 w-5 shrink-0']); ?>
        <div class="flex flex-col gap-1 text-sm">
          <p>
            <span class="font-medium"><?php echo ui_text($labelCode); ?>:</span>
            <span x-text="result && result.item" class="font-mono ml-1"></span>
          </p>
          <p>
            <span class="font-medium"><?php echo ui_text($labelColor); ?>:</span>
            <span x-text="result && result.color" class="font-mono ml-1"></span>
            <span class="mx-2 text-gray-300">/</span>
            <span class="font-medium"><?php echo ui_text($labelSize); ?>:</span>
            <span x-text="result && result.size" class="font-mono ml-1"></span>
          </p>
          <a :href="itemUrl" class="underline"><?php echo ui_text($labelViewItem); ?></a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
