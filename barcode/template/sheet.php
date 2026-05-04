<?php $this->title = 'バーコードシート印刷'; ?>
<?php $this->content = function ($v) {
  $lang = $_SESSION['lang'] ?? 'ja';

  $presetLayouts = [
    ['id' => 'a-one-28332',    'brand' => 'A-ONE',  'code' => '28332',    'name' => 'A-ONE 28332',    'desc' => 'A4 / 24面 (3×8)',  'cols' => 3, 'rows' => 8,  'w_mm' => 70,   'h_mm' => 37],
    ['id' => 'a-one-28383',    'brand' => 'A-ONE',  'code' => '28383',    'name' => 'A-ONE 28383',    'desc' => 'A4 / 12面 (3×4)',  'cols' => 3, 'rows' => 4,  'w_mm' => 70,   'h_mm' => 67.7],
    ['id' => 'a-one-28485',    'brand' => 'A-ONE',  'code' => '28485',    'name' => 'A-ONE 28485',    'desc' => 'A4 / 65面 (5×13)', 'cols' => 5, 'rows' => 13, 'w_mm' => 38.1, 'h_mm' => 21.2],
    ['id' => 'a-one-28720',    'brand' => 'A-ONE',  'code' => '28720',    'name' => 'A-ONE 28720',    'desc' => 'A4 / 21面 (3×7)',  'cols' => 3, 'rows' => 7,  'w_mm' => 70,   'h_mm' => 42.3],
    ['id' => 'kokuyo-kpc-e161','brand' => 'KOKUYO', 'code' => 'KPC-E161', 'name' => 'KOKUYO KPC-E161','desc' => 'A4 / 18面 (3×6)',  'cols' => 3, 'rows' => 6,  'w_mm' => 66.7, 'h_mm' => 46.6],
    ['id' => 'kokuyo-kpc-e162','brand' => 'KOKUYO', 'code' => 'KPC-E162', 'name' => 'KOKUYO KPC-E162','desc' => 'A4 / 24面 (4×6)',  'cols' => 4, 'rows' => 6,  'w_mm' => 50,   'h_mm' => 46.6],
    ['id' => 'kokuyo-kpc-e165','brand' => 'KOKUYO', 'code' => 'KPC-E165', 'name' => 'KOKUYO KPC-E165','desc' => 'A4 / 6面 (2×3)',   'cols' => 2, 'rows' => 3,  'w_mm' => 100,  'h_mm' => 93.1],
    ['id' => 'sanwa-jp-ind77', 'brand' => 'SANWA',  'code' => 'JP-IND77', 'name' => 'SANWA JP-IND77', 'desc' => 'A4 / 21面 (3×7)',  'cols' => 3, 'rows' => 7,  'w_mm' => 70,   'h_mm' => 42.3],
    ['id' => 'custom',         'brand' => '?',      'code' => 'CUSTOM',   'name' => 'カスタム',       'desc' => 'カスタム設定',     'cols' => 3, 'rows' => 8,  'w_mm' => 70,   'h_mm' => 37],
  ];
?>

<div class="alert alert-success d-flex align-items-start gap-2 mb-4">
  <i class="ti ti-shield-check fs-4 flex-shrink-0" aria-hidden="true"></i>
  <div>
    <strong><?php echo $lang === 'ja' ? 'バーコードファースト方式' : 'Barcode-First Workflow'; ?></strong><br>
    <span class="small">
      <?php echo $lang === 'ja'
        ? 'まず管理用バーコードシートを印刷し、あとから「バーコードから商品登録」で商品情報を紐づけることができます。'
        : 'Print management barcode sheets first, then attach product information later via "Register from Barcode".'; ?>
    </span>
  </div>
</div>

<div
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
    presets: <?php echo htmlspecialchars(json_encode($presetLayouts, JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8"); ?>,
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
  <div class="row g-3">

    <!-- Layout selection -->
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-layout-grid me-2" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? 'シートレイアウト選択' : 'Select Sheet Layout'; ?>
          </h3>
        </div>
        <div class="card-body">

          <!-- Search + brand filter -->
          <div class="d-flex flex-column flex-sm-row gap-3 mb-3">
            <div class="flex-fill">
              <input
                x-model="search"
                type="search"
                class="form-control"
                placeholder="<?php echo $lang === 'ja' ? 'シート名・型番で検索...' : 'Search by name or model...'; ?>"
                aria-label="<?php echo $lang === 'ja' ? 'レイアウト検索' : 'Layout search'; ?>"
              >
            </div>
            <select x-model="brand" class="form-select" style="width:auto;" aria-label="<?php echo $lang === 'ja' ? 'ブランドフィルター' : 'Brand filter'; ?>">
              <option value=""><?php echo $lang === 'ja' ? 'すべてのブランド' : 'All Brands'; ?></option>
              <option value="A-ONE">A-ONE</option>
              <option value="KOKUYO">KOKUYO</option>
              <option value="SANWA">SANWA</option>
              <option value="?">カスタム</option>
            </select>
          </div>

          <!-- Layout grid -->
          <div class="row row-cols-1 row-cols-sm-2 g-2" style="max-height:320px;overflow-y:auto;">
            <template x-for="l in filtered" :key="l.id">
              <div class="col">
                <button
                  type="button"
                  @click="selectLayout(l)"
                  class="d-flex align-items-start gap-3 rounded border p-3 text-start w-100 h-100 transition-all"
                  :class="selectedLayout && selectedLayout.id === l.id
                    ? 'border-primary bg-primary-subtle'
                    : 'border-secondary-subtle'"
                  :aria-pressed="selectedLayout && selectedLayout.id === l.id ? 'true' : 'false'"
                >
                  <!-- Grid preview -->
                  <div class="flex-shrink-0 d-flex flex-column gap-1 mt-1" style="width:32px">
                    <template x-for="r in Math.min(l.rows, 4)" :key="r">
                      <div class="d-flex gap-1">
                        <template x-for="c in Math.min(l.cols, 4)" :key="c">
                          <div class="rounded-1"
                            :class="selectedLayout && selectedLayout.id === l.id ? 'bg-primary' : 'bg-secondary-subtle'"
                            style="height:4px;"
                            :style="'width:' + (28 / Math.min(l.cols, 4)) + 'px'">
                          </div>
                        </template>
                      </div>
                    </template>
                  </div>
                  <div>
                    <div class="fw-semibold small" x-text="l.name"></div>
                    <div class="text-muted small" x-text="l.desc"></div>
                    <div class="text-muted small mt-1" x-text="l.w_mm + 'mm × ' + l.h_mm + 'mm'"></div>
                  </div>
                </button>
              </div>
            </template>
            <div x-show="filtered.length === 0" class="col-12 py-5 text-center text-muted small">
              <?php echo $lang === 'ja' ? '一致するシートが見つかりません' : 'No matching sheets found'; ?>
            </div>
          </div>

          <!-- Custom layout fields -->
          <div x-show="selectedLayout && selectedLayout.id === 'custom'" x-transition class="row g-3 mt-2">
            <div class="col-6 col-sm-3">
              <label class="form-label"><?php echo $lang === 'ja' ? '列数' : 'Columns'; ?></label>
              <input type="number" x-model.number="customCols" min="1" max="10" class="form-control form-control-sm" aria-label="列数">
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label"><?php echo $lang === 'ja' ? '行数' : 'Rows'; ?></label>
              <input type="number" x-model.number="customRows" min="1" max="20" class="form-control form-control-sm" aria-label="行数">
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label"><?php echo $lang === 'ja' ? '幅(mm)' : 'Width(mm)'; ?></label>
              <input type="number" x-model.number="customW" min="10" max="200" class="form-control form-control-sm" aria-label="幅">
            </div>
            <div class="col-6 col-sm-3">
              <label class="form-label"><?php echo $lang === 'ja' ? '高さ(mm)' : 'Height(mm)'; ?></label>
              <input type="number" x-model.number="customH" min="10" max="200" class="form-control form-control-sm" aria-label="高さ">
            </div>
          </div>

          <div x-show="!selectedLayout" class="mt-3 small text-muted">
            <?php echo $lang === 'ja' ? '↑ 上のリストからシートレイアウトを選択してください' : '↑ Select a sheet layout from the list above'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Print settings + action -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-printer me-2" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? '印刷設定' : 'Print Settings'; ?>
          </h3>
        </div>
        <div class="card-body vstack gap-3">

          <div>
            <label class="form-label"><?php echo $lang === 'ja' ? 'バーコードプレフィックス' : 'Barcode Prefix'; ?></label>
            <input x-model="prefix" type="text" maxlength="5" class="form-control" placeholder="BC"
                   aria-label="<?php echo $lang === 'ja' ? 'プレフィックス' : 'Prefix'; ?>">
            <div class="form-text"><?php echo $lang === 'ja' ? '例: BC → BC00001, BC00002...' : 'e.g. BC → BC00001, BC00002...'; ?></div>
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
            <input type="hidden" name="layoutId" :value="selectedLayout && selectedLayout.id ? selectedLayout.id : ''">
            <input type="hidden" name="cols"     :value="selectedLayout && selectedLayout.id === 'custom' ? customCols : (selectedLayout ? selectedLayout.cols : 0)">
            <input type="hidden" name="rows"     :value="selectedLayout && selectedLayout.id === 'custom' ? customRows : (selectedLayout ? selectedLayout.rows : 0)">
            <input type="hidden" name="wMm"      :value="selectedLayout && selectedLayout.id === 'custom' ? customW : (selectedLayout ? selectedLayout.w_mm : 0)">
            <input type="hidden" name="hMm"      :value="selectedLayout && selectedLayout.id === 'custom' ? customH : (selectedLayout ? selectedLayout.h_mm : 0)">
            <input type="hidden" name="prefix"   :value="prefix">
            <input type="hidden" name="startNo"  :value="startNo">
            <input type="hidden" name="count"    :value="count">
            <button type="submit" class="btn btn-primary w-100" :disabled="!selectedLayout">
              <i class="ti ti-printer me-2" aria-hidden="true"></i>
              <?php echo $lang === 'ja' ? 'バーコードシートを印刷' : 'Print Barcode Sheet'; ?>
            </button>
          </form>

          <a href="./item/fromBarcode/" class="btn btn-outline-secondary w-100">
            <i class="ti ti-barcode me-2" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? 'バーコードから商品登録 →' : 'Register from Barcode →'; ?>
          </a>

        </div>
      </div>
    </div>

  </div>
</div>

<?php }; ?>
