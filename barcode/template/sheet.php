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

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'バーコードシート印刷' : 'Print Barcode Sheets'; ?></li>
  </ol>
</nav>

<div class="mb-6 alert alert-success">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  <div class="text-sm">
    <strong><?php echo $lang === 'ja' ? 'バーコードファースト方式' : 'Barcode-First Workflow'; ?></strong><br>
    <?php echo $lang === 'ja'
      ? 'まず管理用バーコードシートを印刷し、あとから「バーコードから商品登録」で商品情報を紐づけることができます。'
      : 'Print management barcode sheets first, then attach product information later via "Register from Barcode".'; ?>
  </div>
</div>

<div
  x-data="{
    search: '',
    brand: '',
    selectedLayout: null,
    customCols: 3,
    customRows: 8,
    customW: 70,
    customH: 37,
    startNo: 1,
    count: 24,
    prefix: 'BC',
    presets: <?php echo json_encode($presetLayouts, JSON_UNESCAPED_UNICODE); ?>,
    get filtered() {
      return this.presets.filter(p => {
        const q = this.search.toLowerCase();
        const brandOk = !this.brand || p.brand === this.brand;
        const searchOk = !q || p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q);
        return brandOk && searchOk;
      });
    },
    selectLayout(l) {
      this.selectedLayout = l;
      if (l.id !== 'custom') {
        this.customCols = l.cols;
        this.customRows = l.rows;
        this.customW = l.w_mm;
        this.customH = l.h_mm;
        this.count = l.cols * l.rows;
      }
    },
    get labelsPerSheet() {
      return (this.selectedLayout && this.selectedLayout.id !== 'custom')
        ? this.selectedLayout.cols * this.selectedLayout.rows
        : parseInt(this.customCols) * parseInt(this.customRows);
    }
  }"
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
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3.5 h-5 w-5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
              :class="selectedLayout?.id === l.id
                ? 'border-primary bg-primary bg-opacity-5 dark:bg-meta-4'
                : 'border-stroke hover:border-primary dark:border-strokedark'"
              :aria-pressed="selectedLayout?.id === l.id ? 'true' : 'false'"
            >
              <!-- Grid preview -->
              <div class="shrink-0 flex flex-col gap-0.5 mt-1" :style="'width:32px'">
                <template x-for="r in Math.min(l.rows, 4)" :key="r">
                  <div class="flex gap-0.5">
                    <template x-for="c in Math.min(l.cols, 4)" :key="c">
                      <div class="h-1.5 rounded-sm"
                        :class="selectedLayout?.id === l.id ? 'bg-primary' : 'bg-stroke dark:bg-strokedark'"
                        :style="'width:' + (28 / Math.min(l.cols, 4)) + 'px'">
                      </div>
                    </template>
                  </div>
                </template>
              </div>
              <div>
                <p class="text-sm font-semibold text-black dark:text-white" x-text="l.name"></p>
                <p class="text-xs text-body dark:text-bodydark" x-text="l.desc"></p>
                <p class="text-xs text-body dark:text-bodydark mt-0.5" x-text="l.w_mm + 'mm × ' + l.h_mm + 'mm'"></p>
              </div>
            </button>
          </template>
          <div x-show="filtered.length === 0" class="col-span-2 py-8 text-center text-sm text-body">
            <?php echo $lang === 'ja' ? '一致するシートが見つかりません' : 'No matching sheets found'; ?>
          </div>
        </div>

        <!-- Custom layout fields -->
        <div x-show="selectedLayout?.id === 'custom'" x-transition class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
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

        <div x-show="!selectedLayout" class="mt-4 text-sm text-body dark:text-bodydark">
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
          <p class="mt-1 text-xs text-body"><?php echo $lang === 'ja' ? '例: BC → BC00001, BC00002...' : 'e.g. BC → BC00001, BC00002...'; ?></p>
        </div>
        <div>
          <label class="form-label text-sm"><?php echo $lang === 'ja' ? '開始番号' : 'Start Number'; ?></label>
          <input x-model.number="startNo" type="number" min="1" max="99999" class="form-input py-2 text-sm" aria-label="開始番号">
        </div>
        <div>
          <label class="form-label text-sm"><?php echo $lang === 'ja' ? '枚数' : 'Count'; ?></label>
          <input x-model.number="count" type="number" min="1" max="999" class="form-input py-2 text-sm" :max="labelsPerSheet * 10" aria-label="枚数">
          <p class="mt-1 text-xs text-body">
            <?php echo $lang === 'ja' ? '1シートあたり ' : 'Per sheet: '; ?>
            <span x-text="labelsPerSheet" class="font-semibold"></span>
            <?php echo $lang === 'ja' ? ' 面' : ' labels'; ?>
            （<span x-text="Math.ceil(count / labelsPerSheet)"></span>
            <?php echo $lang === 'ja' ? ' ページ）' : ' page(s)）'; ?>
          </p>
        </div>

        <!-- Selected layout summary -->
        <div x-show="selectedLayout" class="rounded border border-stroke dark:border-strokedark p-3 text-xs text-body dark:text-bodydark">
          <p class="font-semibold text-black dark:text-white mb-1" x-text="selectedLayout?.name"></p>
          <p x-text="selectedLayout?.desc"></p>
          <p x-text="(selectedLayout?.id === 'custom' ? customW : selectedLayout?.w_mm) + 'mm × ' + (selectedLayout?.id === 'custom' ? customH : selectedLayout?.h_mm) + 'mm'"></p>
        </div>

        <form method="post" action="./barcode/printSheet/" target="_blank">
          <input type="hidden" name="layoutId" :value="selectedLayout?.id">
          <input type="hidden" name="cols" :value="selectedLayout?.id === 'custom' ? customCols : selectedLayout?.cols">
          <input type="hidden" name="rows" :value="selectedLayout?.id === 'custom' ? customRows : selectedLayout?.rows">
          <input type="hidden" name="wMm" :value="selectedLayout?.id === 'custom' ? customW : selectedLayout?.w_mm">
          <input type="hidden" name="hMm" :value="selectedLayout?.id === 'custom' ? customH : selectedLayout?.h_mm">
          <input type="hidden" name="prefix" :value="prefix">
          <input type="hidden" name="startNo" :value="startNo">
          <input type="hidden" name="count" :value="count">
          <button type="submit" class="btn-primary w-full" :disabled="!selectedLayout">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <?php echo $lang === 'ja' ? 'バーコードシートを印刷' : 'Print Barcode Sheet'; ?>
          </button>
        </form>

        <a href="./item/fromBarcode/" class="btn-secondary w-full text-center text-sm">
          <?php echo $lang === 'ja' ? 'バーコードから商品登録 →' : 'Register from Barcode →'; ?>
        </a>
      </div>
    </div>

  </div>

</div>

<?php }; ?>
