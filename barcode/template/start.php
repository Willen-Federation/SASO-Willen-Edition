<?php $this->content = function($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $placeholder   = __('ui.barcode.input_placeholder', [], null, 'Input barcode');
    $labelSearch   = $lang === 'ja' ? 'バーコード検索' : 'Barcode Search';
    $labelSubmit   = $lang === 'ja' ? '検索' : 'Search';
    $labelNotFound = $lang === 'ja' ? 'バーコードが見つかりません' : 'Barcode not found';
    $labelUnlinked = $lang === 'ja' ? 'このバーコードはまだ商品に紐付けられていません' : 'This barcode is not yet linked to any item';
    $labelError    = $lang === 'ja' ? '検索エラーが発生しました' : 'Lookup error occurred';
    $labelInvalid  = $lang === 'ja' ? '有効なバーコードを入力してください（PNDxxxxxxxxx または 12桁数字）' : 'Please enter a valid barcode (PNDxxxxxxxxx or 12-digit number)';
?>

<div class="flex justify-center mb-8">
  <div
    x-data="{
      code: '',
      error: null,
      loading: false,

      isPnd()    { return /^PND\d{9}$/.test(this.code.trim()); },
      isLegacy() { return /^\d{12}$/.test(this.code.trim()); },

      async search() {
        const raw = this.code.trim();
        this.error   = null;
        if (!raw) return;

        if (this.isPnd()) {
          this.loading = true;
          try {
            const res  = await fetch('./api/v1/barcode/' + encodeURIComponent(raw));
            const data = await res.json();
            if (!res.ok || !data) {
              this.error = <?php echo json_encode($labelNotFound); ?>;
            } else if (data.item && data.item.id) {
              window.location.href = './item/start/item/' + data.item.id + '/';
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
          window.location.href = './item/start/item/' + item + '/color/' + color + '/size/' + size + '/action/shelf';
        } else {
          this.error = <?php echo json_encode($labelInvalid); ?>;
        }
      }
    }"
    class="w-full max-w-xl"
  >
    <div class="card shadow-md">
      <div class="card-body">
        <h2 class="mb-4 flex items-center gap-2 text-base font-semibold text-black dark:text-white">
          <?php ui('iconHeroicon', ['name' => 'qr', 'class' => 'h-5 w-5 text-primary']); ?>
          <?php echo ui_text($labelSearch); ?>
        </h2>
        <div class="flex gap-3">
          <div class="relative grow">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-gray-300">
              <?php ui('iconHeroicon', ['name' => 'qr', 'class' => 'h-5 w-5']); ?>
            </div>
            <input
              id="barcodeInput"
              x-model="code"
              type="text"
              maxlength="12"
              class="form-input pl-10 w-full"
              placeholder="<?php echo ui_attr($placeholder); ?>"
              @keydown.enter.prevent="search()"
              autocomplete="off"
              autofocus
            >
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

        <div x-show="error" x-cloak class="mt-3 ta-alert ta-alert-danger" role="alert" aria-live="polite">
          <?php ui('iconHeroicon', ['name' => 'x-circle', 'class' => 'h-5 w-5 shrink-0']); ?>
          <span x-text="error"></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
