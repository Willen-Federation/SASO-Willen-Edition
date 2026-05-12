<?php $this->title = 'バーコードシート印刷'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';

  // Popular Japanese label sheets (A-ONE, KOKUYO, etc.)
  $presetLayouts = [
    ['id' => 'a-one-28332', 'brand' => 'A-ONE', 'code' => '28332', 'name' => 'A-ONE 28332', 'desc' => 'A4 / 24面 (3×8)', 'cols' => 3, 'rows' => 8, 'w_mm' => 70, 'h_mm' => 37],
    ['id' => 'a-one-28383', 'brand' => 'A-ONE', 'code' => '28383', 'name' => 'A-ONE 28383', 'desc' => 'A4 / 12面 (3×4)', 'cols' => 3, 'rows' => 4, 'w_mm' => 70, 'h_mm' => 67.7],
    ['id' => 'a-one-28485', 'brand' => 'A-ONE', 'code' => '28485', 'name' => 'A-ONE 28485', 'desc' => 'A4 / 65面 (5×13)', 'cols' => 5, 'rows' => 13, 'w_mm' => 38.1, 'h_mm' => 21.2],
    ['id' => 'a-one-28720', 'brand' => 'A-ONE', 'code' => '28720', 'name' => 'A-ONE 28720', 'desc' => 'A4 / 21面 (3×7)', 'cols' => 3, 'rows' => 7, 'w_mm' => 70, 'h_mm' => 42.3],
    ['id' => 'kokuyo-kpc-e161', 'brand' => 'KOKUYO', 'code' => 'KPC-E161', 'name' => 'KOKUYO KPC-E161', 'desc' => 'A4 / 18面 (3×6)', 'cols' => 3, 'rows' => 6, 'w_mm' => 66.7, 'h_mm' => 46.6],
    ['id' => 'kokuyo-kpc-e162', 'brand' => 'KOKUYO', 'code' => 'KPC-E162', 'name' => 'KOKUYO KPC-E162', 'desc' => 'A4 / 24面 (4×6)', 'cols' => 4, 'rows' => 6, 'w_mm' => 50, 'h_mm' => 46.6],
    ['id' => 'kokuyo-kpc-e165', 'brand' => 'KOKUYO', 'code' => 'KPC-E165', 'name' => 'KOKUYO KPC-E165', 'desc' => 'A4 / 6面 (2×3)', 'cols' => 2, 'rows' => 3, 'w_mm' => 100, 'h_mm' => 93.1],
    ['id' => 'sanwa-jp-ind77', 'brand' => 'SANWA', 'code' => 'JP-IND77', 'name' => 'SANWA JP-IND77', 'desc' => 'A4 / 21面 (3×7)', 'cols' => 3, 'rows' => 7, 'w_mm' => 70, 'h_mm' => 42.3],
    ['id' => 'custom', 'brand' => '?', 'code' => 'CUSTOM', 'name' => 'カスタム', 'desc' => 'カスタム設定', 'cols' => 3, 'rows' => 8, 'w_mm' => 70, 'h_mm' => 37],
  ];
?>

<div class="ta-alert ta-alert-info mb-6">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  <div class="text-sm">
    <strong><?php echo $lang === 'ja' ? 'バーコードファースト方式' : 'Barcode-First Workflow'; ?></strong><br>
    <?php echo $lang === 'ja'
      ? 'まず管理用バーコードシートを印刷し、あとから「バーコードから商品登録」で商品情報を紐づけることができます。'
      : 'Print management barcode sheets first, then attach product information later via "Register from Barcode".'; ?>
  </div>
</div>
<div
  class="p-6 max-w-7xl mx-auto"
  x-data='{
    search: "",
    brand: "",
    selectedLayout: null,
    customCols: 3,
    customRows: 8,
    customW: 70,
    customH: 37,
    startNo: 1,
    count: 24,
    prefix: "BC",
    presets: <?php echo htmlspecialchars(json_encode($presetLayouts, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>,
    get filtered() {
      const q = this.search.toLowerCase();
      return this.presets.filter(p => {
        const bOk = !this.brand || p.brand === this.brand;
        const sOk = !q || p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q);
        return bOk && sOk;
      });
    },
    selectLayout(l) {
      this.selectedLayout = l;
      if (l.id !== "custom") {
        this.customCols = l.cols;
        this.customRows = l.rows;
        this.customW = l.w_mm;
        this.customH = l.h_mm;
        this.count = l.cols * l.rows;
      }
    },
    get labelsPerSheet() {
      return (this.selectedLayout && this.selectedLayout.id !== "custom")
        ? this.selectedLayout.cols * this.selectedLayout.rows
        : parseInt(this.customCols) * parseInt(this.customRows);
    }
  }'
>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <!-- Layout selection -->
    <div class="card lg:col-span-2">
      <div class="card-header">
        <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'シートレイアウト選択' : 'Select Sheet Layout'; ?></h2>
      </div>
      <div class="card-body">
        <!-- Search + brand filter -->
        <div class="mb-4 flex flex-col gap-3 sm:flex-row">
          <div class="relative flex-1">
            <span class="absolute left-3 top-3 text-body">
              <?php ui('iconHeroicon', ['name' => 'list', 'class' => 'h-5 w-5']); ?>
            </span>
            <input
              x-model="search"
              type="search"
              class="form-input pl-11"
              placeholder="<?php echo $lang === 'ja' ? 'シート名・型番で検索...' : 'Search by name or model...'; ?>"
              aria-label="<?php echo $lang === 'ja' ? 'レイアウト検索' : 'Layout search'; ?>"
            >
          </div>
          <select x-model="brand" class="form-select w-full sm:w-40" aria-label="<?php echo $lang === 'ja' ? 'ブランドフィルター' : 'Brand filter'; ?>">
            <option value=""><?php echo $lang === 'ja' ? 'すべてのブランド' : 'All Brands'; ?></option>
            <option value="A-ONE">A-ONE</option>
            <option value="KOKUYO">KOKUYO</option>
            <option value="SANWA">SANWA</option>
            <option value="?">カスタム</option>
          </select>
        </div>

        <!-- Layout grid -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 max-h-80 overflow-y-auto pr-1">
          <template x-for="l in filtered" :key="l.id">
            <button
              type="button"
              @click="selectLayout(l)"
              class="flex items-start gap-3 rounded border p-3 text-left transition"
              :class="selectedLayout && selectedLayout.id === l.id
                ? 'border-brand-500 bg-brand-500 bg-opacity-5 dark:bg-gray-700'
                : 'border-gray-200 hover:border-brand-500 dark:border-gray-800'"
              :aria-pressed="selectedLayout && selectedLayout.id === l.id ? 'true' : 'false'"
            >
              <!-- Grid preview -->
              <div class="shrink-0 flex flex-col gap-0.5 mt-1" :style="'width:32px'">
                <template x-for="r in Math.min(l.rows, 4)" :key="r">
                  <div class="flex gap-0.5">
                    <template x-for="c in Math.min(l.cols, 4)" :key="c">
                      <div class="h-1.5 rounded-sm"
                        :class="selectedLayout && selectedLayout.id === l.id ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-600'"
                        :style="'width:' + (28 / Math.min(l.cols, 4)) + 'px'">
                      </div>
                    </template>
                  </div>
                </template>
              </div>
              <div>
                <p class="text-sm font-semibold text-black dark:text-white" x-text="l.name"></p>
                <p class="text-xs text-gray-600 dark:text-gray-400" x-text="l.desc"></p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5" x-text="l.w_mm + 'mm × ' + l.h_mm + 'mm'"></p>
              </div>
            </button>
          </template>
          <div x-show="filtered.length === 0" class="col-span-2 py-8 text-center text-sm text-gray-600">
            <?php echo $lang === 'ja' ? '一致するシートが見つかりません' : 'No matching sheets found'; ?>
          </div>
        </div>

        <!-- Custom layout fields -->
        <div x-show="selectedLayout && selectedLayout.id === 'custom'" x-transition class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div>
            <label class="form-label text-xs"><?php echo $lang === 'ja' ? '列数' : 'Columns'; ?></label>
            <input type="number" x-model.number="customCols" min="1" max="10" class="form-input py-2 text-sm" aria-label="列数">
          </div>
          <div>
            <label class="form-label text-xs"><?php echo $lang === 'ja' ? '行数' : 'Rows'; ?></label>
            <input type="number" x-model.number="customRows" min="1" max="20" class="form-input py-2 text-sm" aria-label="行数">
          </div>
          <div>
            <label class="form-label text-xs"><?php echo $lang === 'ja' ? '幅(mm)' : 'Width(mm)'; ?></label>
            <input type="number" x-model.number="customW" min="10" max="200" class="form-input py-2 text-sm" aria-label="幅">
          </div>
          <div>
            <label class="form-label text-xs"><?php echo $lang === 'ja' ? '高さ(mm)' : 'Height(mm)'; ?></label>
            <input type="number" x-model.number="customH" min="10" max="200" class="form-input py-2 text-sm" aria-label="高さ">
          </div>
        </div>

        <div x-show="!selectedLayout" class="mt-4 text-sm text-gray-600 dark:text-gray-400">
          <?php echo $lang === 'ja' ? '↑ 上のリストからシートレイアウトを選択してください' : '↑ Select a sheet layout from the list above'; ?>
        </div>
      </div>
    </div>

    <!-- Print settings + action -->
    <div class="card">
      <div class="card-header">
        <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '印刷設定' : 'Print Settings'; ?></h2>
      </div>
      <div class="card-body space-y-4">
        <div>
          <label class="form-label text-sm"><?php echo $lang === 'ja' ? 'バーコードプレフィックス' : 'Barcode Prefix'; ?></label>
          <input x-model="prefix" type="text" maxlength="5" class="form-input py-2 text-sm" placeholder="BC" aria-label="<?php echo $lang === 'ja' ? 'プレフィックス' : 'Prefix'; ?>">
          <p class="mt-1 text-xs text-gray-600"><?php echo $lang === 'ja' ? '例: BC → BC00001, BC00002...' : 'e.g. BC → BC00001, BC00002...'; ?></p>
        </div>
        <div>
          <label class="form-label text-sm"><?php echo $lang === 'ja' ? '開始番号' : 'Start Number'; ?></label>
          <input x-model.number="startNo" type="number" min="1" max="99999" class="form-input py-2 text-sm" aria-label="開始番号">
        </div>
        <div>
          <label class="form-label text-sm"><?php echo $lang === 'ja' ? '枚数' : 'Count'; ?></label>
          <input x-model.number="count" type="number" min="1" max="999" class="form-input py-2 text-sm" :max="labelsPerSheet * 10" aria-label="枚数">
          <p class="mt-1 text-xs text-gray-600">
            <?php echo $lang === 'ja' ? '1シートあたり ' : 'Per sheet: '; ?>
            <span x-text="labelsPerSheet" class="font-semibold"></span>
            <?php echo $lang === 'ja' ? ' 面' : ' labels'; ?>
            （<span x-text="Math.ceil(count / labelsPerSheet)"></span>
            <?php echo $lang === 'ja' ? ' ページ）' : ' page(s)）'; ?>
          </p>
        </div>

        <!-- Selected layout summary -->
        <div x-show="selectedLayout" class="rounded border border-gray-200 dark:border-gray-800 p-3 text-xs text-gray-600 dark:text-gray-400">
          <p class="font-semibold text-black dark:text-white mb-1" x-text="selectedLayout && selectedLayout.name ? selectedLayout.name : ''"></p>
          <p x-text="selectedLayout && selectedLayout.desc ? selectedLayout.desc : ''"></p>
          <p x-text="(selectedLayout && selectedLayout.id === 'custom' ? customW : (selectedLayout ? selectedLayout.w_mm : 0)) + 'mm × ' + (selectedLayout && selectedLayout.id === 'custom' ? customH : (selectedLayout ? selectedLayout.h_mm : 0)) + 'mm'"></p>
        </div>

        <form method="post" action="./barcode/printSheet/" target="_blank">
          <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
          <input type="hidden" name="layoutId" :value="selectedLayout && selectedLayout.id ? selectedLayout.id : ''">
          <input type="hidden" name="cols"     :value="selectedLayout && selectedLayout.id === 'custom' ? customCols : (selectedLayout ? selectedLayout.cols : 0)">
          <input type="hidden" name="rows"     :value="selectedLayout && selectedLayout.id === 'custom' ? customRows : (selectedLayout ? selectedLayout.rows : 0)">
          <input type="hidden" name="wMm"      :value="selectedLayout && selectedLayout.id === 'custom' ? customW : (selectedLayout ? selectedLayout.w_mm : 0)">
          <input type="hidden" name="hMm"      :value="selectedLayout && selectedLayout.id === 'custom' ? customH : (selectedLayout ? selectedLayout.h_mm : 0)">
          <input type="hidden" name="prefix"   :value="prefix">
          <input type="hidden" name="startNo"  :value="startNo">
          <input type="hidden" name="count"    :value="count">
          <button type="submit" class="btn btn-primary w-full" :disabled="!selectedLayout">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <?php echo $lang === 'ja' ? 'バーコードシートを印刷' : 'Print Barcode Sheet'; ?>
          </button>
        </form>

        <a href="./item/fromBarcode/" class="btn btn-secondary w-full">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
          <?php echo $lang === 'ja' ? 'バーコードから商品登録 →' : 'Register from Barcode →'; ?>
        </a>

        <!-- ── Save layout to label database ───────────────────────── -->
        <div x-show="selectedLayout && selectedLayout.id !== 'custom'" x-transition>
          <hr class="my-3" style="border-color:var(--saso-card-bdr)">
          <p class="text-xs mb-2 flex items-center gap-1" style="color:var(--saso-text-sub)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            <?php echo $lang === 'ja' ? 'このレイアウトをラベル寸法に登録' : 'Save layout as label size'; ?>
          </p>
          <form method="post" action="./label/add/">
            <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
            <div class="mb-2">
              <input
                type="text"
                name="labelName"
                x-model="saveLabelName"
                class="form-input text-sm py-2 w-full"
                maxlength="50"
                pattern="^[0-9A-Za-z_-]{1,50}$"
                placeholder="<?php echo $lang === 'ja' ? 'ラベル名（半角英数・ハイフン）' : 'Label name (alphanumeric/-)'; ?>"
                required
              >
            </div>
            <input type="hidden" name="width"          :value="selectedLayout ? selectedLayout.w_mm : 0">
            <input type="hidden" name="height"         :value="selectedLayout ? selectedLayout.h_mm : 0">
            <input type="hidden" name="marginLeft"     :value="selectedLayout ? selectedLayout.margin_left : 0">
            <input type="hidden" name="marginTop"      :value="selectedLayout ? selectedLayout.margin_top : 0">
            <input type="hidden" name="intervalColumn" value="0">
            <input type="hidden" name="intervalRow"    value="0">
            <button type="submit" class="btn btn-secondary btn-sm w-full"
                    :disabled="!selectedLayout || !saveLabelName">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <?php echo $lang === 'ja' ? 'ラベル寸法として保存' : 'Save as label size'; ?>
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>

  </div>

<?php }; ?>

