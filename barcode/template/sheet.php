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

<div class="mb-6 alert alert-success">
  <?php ui('iconHeroicon', ['name' => 'shield', 'class' => 'h-5 w-5 shrink-0']); ?>
  <div class="text-sm">
    <strong><?php echo $lang === 'ja' ? 'バーコードファースト方式' : 'Barcode-First Workflow'; ?></strong><br>
    <?php echo $lang === 'ja'
      ? 'まず管理用バーコードシートを印刷し、あとから「バーコードから商品登録」で商品情報を紐づけることができます。'
      : 'Print management barcode sheets first, then attach product information later via "Register from Barcode".'; ?>
  </div>
</div>

<script src="https://cdn.tailwindcss.com"></script>
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

          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '開始番号' : 'Start Number'; ?></label>
            <input x-model.number="startNo" type="number" min="1" max="99999" class="form-control" aria-label="開始番号">
          </div>

          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? '枚数' : 'Count'; ?></label>
            <input x-model.number="count" type="number" min="1" max="999" class="form-control" :max="labelsPerSheet * 10" aria-label="枚数">
            <div class="form-text">
              <?php echo $lang === 'ja' ? '1シートあたり ' : 'Per sheet: '; ?>
              <strong x-text="labelsPerSheet"></strong>
              <?php echo $lang === 'ja' ? ' 面' : ' labels'; ?>
              （<span x-text="Math.ceil(count / labelsPerSheet)"></span>
              <?php echo $lang === 'ja' ? ' ページ）' : ' page(s)）'; ?>
            </div>
          </div>

          <!-- Selected layout summary -->
          <div x-show="selectedLayout" class="border rounded p-3 small text-muted">
            <div class="fw-semibold text-body mb-1" x-text="selectedLayout && selectedLayout.name ? selectedLayout.name : ''"></div>
            <div x-text="selectedLayout && selectedLayout.desc ? selectedLayout.desc : ''"></div>
            <div x-text="(selectedLayout && selectedLayout.id === 'custom' ? customW : (selectedLayout ? selectedLayout.w_mm : 0)) + 'mm × ' + (selectedLayout && selectedLayout.id === 'custom' ? customH : (selectedLayout ? selectedLayout.h_mm : 0)) + 'mm'"></div>
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
            <button type="submit" class="btn btn-primary w-100" :disabled="!selectedLayout">
              <i class="bi bi-printer me-2" aria-hidden="true"></i>
              <?php echo $lang === 'ja' ? 'バーコードシートを印刷' : 'Print Barcode Sheet'; ?>
            </span>
          </button>
        </form>

          <a href="./item/fromBarcode/" class="btn btn-outline-secondary w-100">
            <i class="bi bi-barcode me-2" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? 'バーコードから商品登録 →' : 'Register from Barcode →'; ?>
          </a>

          <!-- ── Save layout to label database ───────────────────────── -->
          <div x-show="selectedLayout && selectedLayout.id !== 'custom'" x-transition>
            <hr class="my-2">
            <p class="small text-muted mb-2">
              <i class="bi bi-floppy me-1" aria-hidden="true"></i>
              <?php echo $lang === 'ja' ? 'このレイアウトをラベル寸法に登録' : 'Save layout as label size'; ?>
            </p>
            <form method="post" action="./label/add/">
              <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
              <div class="mb-2">
                <input
                  type="text"
                  name="labelName"
                  x-model="saveLabelName"
                  class="form-control form-control-sm"
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
              <button type="submit" class="btn btn-outline-success btn-sm w-100"
                      :disabled="!selectedLayout || !saveLabelName">
                <i class="bi bi-plus me-1" aria-hidden="true"></i>
                <?php echo $lang === 'ja' ? 'ラベル寸法として保存' : 'Save as label size'; ?>
              </button>
            </form>
          </div>

        </div>
      </div>
    </div>

  </div>

</div>

<?php }; ?>

