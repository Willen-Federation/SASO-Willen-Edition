<?php $this->title = '棚番簡易設定'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>



<script src="https://cdn.tailwindcss.com"></script>
<div
  class="p-6 max-w-7xl mx-auto"
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

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <!-- Left: Input form -->
    <div class="card">
      <div class="card-header flex items-center justify-between">
        <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '棚番・エリアコード入力' : 'Shelf / Area Code Entry'; ?></h2>
        <button type="button" @click="addRow()" class="btn-primary btn-sm text-sm px-4 py-2">
          + <?php echo $lang === 'ja' ? '行を追加' : 'Add Row'; ?>
        </button>
      </div>
      <div class="card-body">
        <form method="post" action="./shelf/simpleSave/" id="shelfSimpleForm">

          <div class="mb-4 overflow-x-auto">
            <table class="data-table w-full" aria-label="<?php echo $lang === 'ja' ? '棚番入力表' : 'Shelf entry table'; ?>">
              <thead>
                <tr>
                  <th class="w-8 pl-4">#</th>
                  <th><?php echo $lang === 'ja' ? '棚番号' : 'Shelf No.'; ?> <span class="text-danger">*</span></th>
                  <th><?php echo $lang === 'ja' ? 'エリアコード' : 'Area Code'; ?></th>
                  <th><?php echo $lang === 'ja' ? 'メモ' : 'Note'; ?></th>
                  <th class="w-8"></th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(row, i) in rows" :key="i">
                  <tr :class="selectedRow === i ? 'bg-primary bg-opacity-5' : ''">
                    <td class="pl-4">
                      <button type="button" @click="selectedRow = (selectedRow === i ? null : i)"
                        class="h-6 w-6 rounded-full text-xs flex items-center justify-center border"
                        :class="selectedRow === i ? 'bg-primary text-white border-primary' : 'border-stroke text-body'"
                        :aria-label="'行' + (i+1) + 'を選択'">
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
                        class="form-input py-2 text-sm"
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
                        class="form-input py-2 text-sm"
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
                        class="form-input py-2 text-sm"
                        :placeholder="'<?php echo $lang === 'ja' ? 'メモ（任意）' : 'Note'; ?>'"
                        :aria-label="'メモ ' + (i+1)"
                      >
                    </td>
                    <td>
                      <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                        class="text-danger hover:text-meta-1" :aria-label="'行' + (i+1) + 'を削除'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
              <input type="hidden" :name="'pin[' + pi + '][x]'" :value="pin.x">
              <input type="hidden" :name="'pin[' + pi + '][y]'" :value="pin.y">
            </div>
          </template>

          <div class="flex gap-3 mt-4">
            <button type="submit" class="btn-primary px-8">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <?php echo $lang === 'ja' ? '保存する' : 'Save'; ?>
            </button>
            <a href="./shelf/label/" class="btn-secondary px-6">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              <?php echo $lang === 'ja' ? '棚番シール印刷' : 'Print Shelf Labels'; ?>
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Map panel -->
    <div class="card">
      <div class="card-header flex items-center justify-between">
        <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'フロアマップ（任意）' : 'Floor Map (Optional)'; ?></h2>
        <label class="toggle" aria-label="マップモード切替">
          <input type="checkbox" x-model="mapMode">
          <span class="toggle-slider"></span>
        </label>
      </div>
      <div class="card-body" x-show="mapMode" x-transition>
        <p class="text-sm text-body dark:text-bodydark mb-4">
          <?php echo $lang === 'ja'
            ? 'マップ画像をアップロードして、左の表で行を選択してからマップ上をクリックすると棚の位置をピンで設定できます。'
            : 'Upload a map image, select a row on the left, then click the map to set the shelf position pin.'; ?>
        </p>

        <label class="mb-4 block">
          <span class="form-label"><?php echo $lang === 'ja' ? 'マップ画像をアップロード' : 'Upload Map Image'; ?></span>
          <input
            type="file"
            accept="image/*"
            @change="handleMap($event)"
            class="block w-full text-sm text-body file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-opacity-90"
            aria-label="<?php echo $lang === 'ja' ? 'マップ画像' : 'Map image'; ?>"
          >
        </label>

        <div x-show="mapPreview" class="relative mt-2 rounded border border-stroke dark:border-strokedark overflow-hidden" style="min-height:200px">
          <img
            :src="mapPreview"
            @click="addPin($event)"
            class="w-full cursor-crosshair select-none"
            alt="<?php echo $lang === 'ja' ? 'フロアマップ' : 'Floor map'; ?>"
            draggable="false"
          >
          <!-- Pins -->
          <template x-for="(pin, pi) in pins" :key="pi">
            <div
              class="absolute flex items-center justify-center"
              :style="'left:' + pin.x + '%;top:' + pin.y + '%;transform:translate(-50%,-100%)'"
            >
              <div class="flex flex-col items-center">
                <div class="bg-primary text-white text-xs px-1.5 py-0.5 rounded shadow font-bold" x-text="rows[pin.row]?.shelfNo || (pin.row+1)"></div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary drop-shadow" style="width: 1.25rem; height: 1.25rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
              </div>
            </div>
          </template>
        </div>

        <p x-show="!mapPreview" class="text-center text-sm text-body dark:text-bodydark mt-8">
          <?php echo $lang === 'ja' ? '画像をアップロードするとここに表示されます' : 'Upload an image to display it here'; ?>
        </p>

        <p x-show="mapPreview && selectedRow === null" class="mt-3 text-xs text-warning">
          <?php echo $lang === 'ja' ? '⚠ 左の表で行（番号）をクリックしてからマップを操作してください' : '⚠ Click a row number on the left, then click the map'; ?>
        </p>
        <p x-show="selectedRow !== null" class="mt-3 text-xs text-success">
          <?php echo $lang === 'ja' ? '✓ 選択中：' : '✓ Selected row: '; ?><span x-text="selectedRow !== null ? rows[selectedRow]?.shelfNo || (selectedRow+1) : ''"></span>
        </p>
      </div>
      <div class="card-body" x-show="!mapMode">
        <div class="flex flex-col items-center gap-3 py-8 text-body dark:text-bodydark">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" style="width: 3rem; height: 3rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 16l4.553-2.276A1 1 0 0021 19.382V8.618a1 1 0 00-.553-.894L15 5m0 13V5m0 0L9 7"/></svg>
          <p class="text-sm"><?php echo $lang === 'ja' ? 'マップモードを有効にするとフロアマップ上に棚の位置をピンで設定できます' : 'Enable map mode to pin shelf positions on a floor map'; ?></p>
        </div>
      </div>
    </div>

  </div>

</div>

<?php }; ?>
