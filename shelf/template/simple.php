<?php $this->title = '棚番簡易設定'; ?>
<?php $this->content = function ($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<div
  x-data="{
    rows: [{shelfNo: '', area: '', note: ''}],
    mapMode: false,
    mapFile: null,
    mapPreview: null,
    pins: [],
    selectedRow: null,
    addRow() { this.rows.push({shelfNo: '', area: '', note: ''}); },
    removeRow(i) { this.rows.splice(i, 1); },
    handleMap(e) {
      const f = e.target.files[0];
      if (!f) return;
      this.mapFile = f;
      const r = new FileReader();
      r.onload = ev => { this.mapPreview = ev.target.result; };
      r.readAsDataURL(f);
    },
    addPin(e) {
      if (!this.selectedRow && this.selectedRow !== 0) return;
      const rect = e.target.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width * 100).toFixed(1);
      const y = ((e.clientY - rect.top) / rect.height * 100).toFixed(1);
      const existing = this.pins.findIndex(p => p.row === this.selectedRow);
      if (existing >= 0) { this.pins[existing] = {row: this.selectedRow, x, y}; }
      else { this.pins.push({row: this.selectedRow, x, y}); }
    },
    pinFor(i) { return this.pins.find(p => p.row === i); }
  }"
>
  <div class="row g-3">

    <!-- Left: Input form -->
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-grid-dots me-2" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? '棚番・エリアコード入力' : 'Shelf / Area Code Entry'; ?>
          </h3>
          <div class="card-options">
            <button type="button" @click="addRow()" class="btn btn-primary btn-sm">
              <i class="ti ti-plus me-1" aria-hidden="true"></i>
              <?php echo $lang === 'ja' ? '行を追加' : 'Add Row'; ?>
            </button>
          </div>
        </div>
        <div class="card-body">
          <form method="post" action="./shelf/simpleSave/" id="shelfSimpleForm">

            <div class="table-responsive mb-3">
              <table class="table table-vcenter" aria-label="<?php echo $lang === 'ja' ? '棚番入力表' : 'Shelf entry table'; ?>">
                <thead>
                  <tr>
                    <th style="width:2.5rem;">#</th>
                    <th><?php echo $lang === 'ja' ? '棚番号' : 'Shelf No.'; ?> <span class="text-danger">*</span></th>
                    <th><?php echo $lang === 'ja' ? 'エリアコード' : 'Area Code'; ?></th>
                    <th><?php echo $lang === 'ja' ? 'メモ' : 'Note'; ?></th>
                    <th style="width:2.5rem;"></th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="(row, i) in rows" :key="i">
                    <tr :class="selectedRow === i ? 'table-active' : ''">
                      <td>
                        <button type="button"
                          @click="selectedRow = (selectedRow === i ? null : i)"
                          class="btn btn-sm rounded-pill"
                          :class="selectedRow === i ? 'btn-primary' : 'btn-outline-secondary'"
                          :aria-label="'行' + (i+1) + 'を選択'"
                          :aria-pressed="selectedRow === i ? 'true' : 'false'">
                          <span x-text="i+1"></span>
                        </button>
                      </td>
                      <td>
                        <input
                          type="text"
                          :name="'shelf[' + i + '][number]'"
                          x-model="row.shelfNo"
                          maxlength="15"
                          pattern="^[0-9A-Za-z\-]+$"
                          class="form-control form-control-sm"
                          :placeholder="'<?php echo $lang === 'ja' ? '例: A-01' : 'e.g. A-01'; ?>'"
                          :required="i === 0"
                          :aria-label="'棚番号 ' + (i+1)"
                        >
                      </td>
                      <td>
                        <input
                          type="text"
                          :name="'shelf[' + i + '][area]'"
                          x-model="row.area"
                          maxlength="10"
                          class="form-control form-control-sm"
                          :placeholder="'<?php echo $lang === 'ja' ? '例: ZONE-A' : 'e.g. ZONE-A'; ?>'"
                          :aria-label="'エリアコード ' + (i+1)"
                        >
                      </td>
                      <td>
                        <input
                          type="text"
                          :name="'shelf[' + i + '][note]'"
                          x-model="row.note"
                          maxlength="50"
                          class="form-control form-control-sm"
                          :placeholder="'<?php echo $lang === 'ja' ? 'メモ（任意）' : 'Note'; ?>'"
                          :aria-label="'メモ ' + (i+1)"
                        >
                      </td>
                      <td>
                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                          class="btn btn-ghost-danger btn-sm" :aria-label="'行' + (i+1) + 'を削除'">
                          <i class="ti ti-x" aria-hidden="true"></i>
                        </button>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <!-- Map pin coordinates (hidden) -->
            <template x-for="(pin, pi) in pins" :key="pi">
              <div>
                <input type="hidden" :name="'pin[' + pi + '][row]'" :value="pin.row">
                <input type="hidden" :name="'pin[' + pi + '][x]'"   :value="pin.x">
                <input type="hidden" :name="'pin[' + pi + '][y]'"   :value="pin.y">
              </div>
            </template>

            <div class="d-flex gap-3 mt-3">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-2" aria-hidden="true"></i>
                <?php echo $lang === 'ja' ? '保存する' : 'Save'; ?>
              </button>
              <a href="./shelf/label/" class="btn btn-outline-secondary">
                <i class="ti ti-printer me-2" aria-hidden="true"></i>
                <?php echo $lang === 'ja' ? '棚番シール印刷' : 'Print Shelf Labels'; ?>
              </a>
            </div>

          </form>
        </div>
      </div>
    </div>

    <!-- Right: Map panel -->
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h3 class="card-title">
            <i class="ti ti-map me-2" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? 'フロアマップ（任意）' : 'Floor Map (Optional)'; ?>
          </h3>
          <div class="card-options">
            <label class="form-check form-switch mb-0" aria-label="マップモード切替">
              <input type="checkbox" class="form-check-input" x-model="mapMode">
              <span class="form-check-label"><?php echo $lang === 'ja' ? 'マップモード' : 'Map mode'; ?></span>
            </label>
          </div>
        </div>

        <div class="card-body" x-show="mapMode" x-transition>
          <p class="small text-muted mb-3">
            <?php echo $lang === 'ja'
              ? 'マップ画像をアップロードして、左の表で行を選択してからマップ上をクリックすると棚の位置をピンで設定できます。'
              : 'Upload a map image, select a row on the left, then click the map to set the shelf position pin.'; ?>
          </p>

          <div class="mb-3">
            <label class="form-label"><?php echo $lang === 'ja' ? 'マップ画像をアップロード' : 'Upload Map Image'; ?></label>
            <input
              type="file"
              accept="image/*"
              @change="handleMap($event)"
              class="form-control"
              aria-label="<?php echo $lang === 'ja' ? 'マップ画像' : 'Map image'; ?>"
            >
          </div>

          <div x-show="mapPreview" class="position-relative mt-2 rounded border overflow-hidden" style="min-height:200px">
            <img
              :src="mapPreview"
              @click="addPin($event)"
              class="w-100 d-block user-select-none"
              style="cursor:crosshair;"
              alt="<?php echo $lang === 'ja' ? 'フロアマップ' : 'Floor map'; ?>"
              draggable="false"
            >
            <!-- Pins -->
            <template x-for="(pin, pi) in pins" :key="pi">
              <div
                class="position-absolute d-flex flex-column align-items-center"
                :style="'left:' + pin.x + '%;top:' + pin.y + '%;transform:translate(-50%,-100%);pointer-events:none;'"
              >
                <span class="badge bg-primary fw-bold mb-1" x-text="rows[pin.row]?.shelfNo || (pin.row+1)"></span>
                <i class="ti ti-map-pin text-primary fs-4" aria-hidden="true"></i>
              </div>
            </template>
          </div>

          <p x-show="!mapPreview" class="text-center small text-muted mt-4">
            <?php echo $lang === 'ja' ? '画像をアップロードするとここに表示されます' : 'Upload an image to display it here'; ?>
          </p>
          <p x-show="mapPreview && selectedRow === null" class="mt-3 small text-warning">
            <i class="ti ti-alert-triangle me-1" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? '左の表で行（番号）をクリックしてからマップを操作してください' : 'Click a row number on the left, then click the map'; ?>
          </p>
          <p x-show="selectedRow !== null" class="mt-3 small text-success">
            <i class="ti ti-check me-1" aria-hidden="true"></i>
            <?php echo $lang === 'ja' ? '選択中：' : 'Selected row: '; ?><span x-text="selectedRow !== null ? rows[selectedRow]?.shelfNo || (selectedRow+1) : ''"></span>
          </p>
        </div>

        <div class="card-body" x-show="!mapMode">
          <div class="d-flex flex-column align-items-center gap-3 py-5 text-muted">
            <i class="ti ti-map-2 fs-1" aria-hidden="true"></i>
            <p class="small mb-0"><?php echo $lang === 'ja' ? 'マップモードを有効にするとフロアマップ上に棚の位置をピンで設定できます' : 'Enable map mode to pin shelf positions on a floor map'; ?></p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php }; ?>
