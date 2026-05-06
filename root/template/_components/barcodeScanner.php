<?php
/*
 * Barcode Scanner component.
 *
 * Props (via local variables before include, or passed as $args when called
 * through ui()):
 *   $inputId     string  (required) — id of the <input> that receives the scanned value
 *   $buttonLabel string  (optional) — button text; falls back to ui.scanner.open translation
 *   $buttonClass string  (optional) — extra CSS classes on the trigger button
 *   $uniqueId    string  (optional) — suffix to keep HTML ids unique when >1 instance on page
 *
 * Usage example:
 *   <?php
 *   $inputId = 'barcode-input';
 *   include __DIR__ . '/path/to/barcodeScanner.php';
 *   ?>
 */

$inputId     = $inputId     ?? '';
$buttonLabel = $buttonLabel ?? __('ui.scanner.open', [], null, 'Scan Barcode / QR');
$buttonClass = $buttonClass ?? '';
$uniqueId    = $uniqueId    ?? uniqid('bs_');

$readerId  = 'saso-qr-reader-' . $uniqueId;
$wrapperId = 'saso-scanner-wrapper-' . $uniqueId;
?>

<div id="<?php echo htmlspecialchars($wrapperId, ENT_QUOTES, 'UTF-8'); ?>"
     x-data="{
       ...sasoScanner(),
       _readerId: <?php echo htmlspecialchars(json_encode($readerId), ENT_QUOTES, 'UTF-8'); ?>,
       _targetInput: <?php echo htmlspecialchars(json_encode($inputId), ENT_QUOTES, 'UTF-8'); ?>,

       openScanner() {
         this.result = null;
         this.error  = null;
         this.active = true;
         this.$nextTick(() => {
           if (typeof Html5Qrcode === 'undefined') {
             this.error = 'Scanner library not loaded.';
             return;
           }
           const el = document.getElementById(this._readerId);
           if (!el) { this.error = 'Scanner element not found.'; return; }
           this.scanner = new Html5Qrcode(this._readerId);
           Html5Qrcode.getCameras()
             .then((cameras) => {
               if (!cameras || cameras.length === 0) { this.error = 'No camera found.'; return; }
               const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[cameras.length - 1];
               return this.scanner.start(
                 cam.id,
                 { fps: 10, qrbox: { width: 250, height: 250 } },
                 (code) => {
                   this.result = code;
                   this.$dispatch('barcode-detected', { code, targetInput: this._targetInput });
                   this.closeScanner();
                 },
                 () => {}
               );
             })
             .catch((err) => {
               const msg = (err && err.message) ? err.message : String(err);
               this.error = /denied|NotAllowed/i.test(msg) ? 'camera_denied' : msg;
             });
         });
       },

       closeScanner() {
         this.stopScan();
         this.active = false;
       },
     }"
     @barcode-detected.window="
       if ($event.detail.targetInput === _targetInput) {
         const inp = document.getElementById(_targetInput);
         if (inp) { inp.value = $event.detail.code; inp.dispatchEvent(new Event('input')); }
       }
     "
>

  {{-- Trigger button --}}
  <button
    type="button"
    @click="openScanner()"
    class="btn btn-secondary d-inline-flex align-items-center gap-2 <?php echo htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8'); ?>"
    aria-label="<?php echo ui_attr(__('ui.scanner.open', [], null, 'Scan Barcode / QR')); ?>"
  >
    <i class="bi bi-qr-code" aria-hidden="true"></i>
    <span><?php echo ui_text($buttonLabel); ?></span>
  </button>

  {{-- Scanner overlay --}}
  <div
    x-show="active"
    x-cloak
    class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
    style="z-index:9999;"
    role="dialog"
    aria-modal="true"
    aria-label="<?php echo ui_attr(__('ui.scanner.open', [], null, 'Scan Barcode / QR')); ?>"
    @keydown.escape.window="closeScanner()"
  >
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background:rgba(0,0,0,.7);" @click="closeScanner()"></div>

    <div class="position-relative bg-white rounded shadow-lg p-4" style="width:100%;max-width:24rem;z-index:1;"
         x-trap.inert.noscroll="active">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h6 fw-semibold mb-0">
          <?php echo ui_text(__('ui.scanner.open', [], null, 'Scan Barcode / QR')); ?>
        </h2>
        <button
          type="button"
          @click="closeScanner()"
          class="btn-close"
          aria-label="<?php echo ui_attr(__('ui.scanner.close', [], null, 'Close')); ?>"
        ></button>
      </div>

      <p x-show="!error" class="mb-3 small text-muted">
        <?php echo ui_text(__('ui.scanner.scanning', [], null, 'Scanning…')); ?>
      </p>

      <div
        id="<?php echo htmlspecialchars($readerId, ENT_QUOTES, 'UTF-8'); ?>"
        class="rounded overflow-hidden bg-black"
        style="min-height:250px;"
        x-show="!error"
      ></div>

      <div
        x-show="error"
        class="alert alert-danger mt-3"
        role="alert"
      >
        <template x-if="error === 'camera_denied'">
          <span><?php echo ui_text(__('ui.scanner.camera_denied', [], null, 'Camera access denied. Please allow camera access and try again.')); ?></span>
        </template>
        <template x-if="error && error !== 'camera_denied'">
          <span x-text="error"></span>
        </template>
      </div>

      <button type="button" @click="closeScanner()" class="btn btn-secondary mt-4 w-100">
        <?php echo ui_text(__('ui.scanner.close', [], null, 'Close')); ?>
      </button>
    </div>
  </div>

</div>
