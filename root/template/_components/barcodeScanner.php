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
$lang      = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$scannerConfig = [
    'readerId'    => $readerId,
    'targetInput' => $inputId,
    'messages'    => [
        'libraryMissing' => $lang === 'ja' ? 'スキャナーライブラリを読み込めませんでした。ページを再読み込みしてください。' : 'Scanner library was not loaded. Reload the page and try again.',
        'elementMissing' => $lang === 'ja' ? 'スキャナー表示領域が見つかりません。' : 'Scanner element was not found.',
        'noCamera'       => $lang === 'ja' ? 'Webカメラが見つかりません。物理バーコードリーダーをご利用の場合は入力欄にカーソルを合わせて読み取ってください。' : 'No web camera was found. If you use a physical barcode reader, focus the input field and scan there.',
    ],
];
$scannerConfigJson = json_encode($scannerConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>

<div id="<?php echo htmlspecialchars($wrapperId, ENT_QUOTES, 'UTF-8'); ?>"
     x-data='sasoBarcodeScanner(<?php echo htmlspecialchars($scannerConfigJson ?: '{}', ENT_QUOTES, 'UTF-8'); ?>)'
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
      <p class="mb-3 small text-muted">
        <?php echo ui_text($lang === 'ja' ? 'Webカメラで読み取れない場合は、入力欄にカーソルを合わせてバーコードリーダー本体で読み取れます。' : 'If camera scanning is unavailable, focus the input field and scan with a hardware barcode reader.'); ?>
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

      <button type="button" @click="closeScanner()" class="btn btn-secondary mt-4 w-100" :disabled="stopping">
        <span x-show="!stopping"><?php echo ui_text(__('ui.scanner.close', [], null, 'Close')); ?></span>
        <span x-show="stopping"><?php echo ui_text($lang === 'ja' ? '停止中...' : 'Stopping...'); ?></span>
      </button>
    </div>
  </div>

</div>
