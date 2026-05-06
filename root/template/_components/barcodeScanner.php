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
    class="btn btn-secondary inline-flex items-center gap-2 <?php echo htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8'); ?>"
    aria-label="<?php echo ui_attr(__('ui.scanner.open', [], null, 'Scan Barcode / QR')); ?>"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 9V5a2 2 0 012-2h4M3 15v4a2 2 0 002 2h4m6-18h4a2 2 0 012 2v4m0 6v4a2 2 0 01-2 2h-4
           M9 9h1v1H9zm0 5h1v1H9zm5-5h1v1h-1zm0 5h1v1h-1z"/>
    </svg>
    <span><?php echo ui_text($buttonLabel); ?></span>
  </button>

  {{-- Scanner overlay --}}
  <div
    x-show="active"
    x-cloak
    class="fixed inset-0 z-99999 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
    aria-label="<?php echo ui_attr(__('ui.scanner.open', [], null, 'Scan Barcode / QR')); ?>"
    @keydown.escape.window="closeScanner()"
  >
    <div class="absolute inset-0 bg-black/70" @click="closeScanner()"></div>

    <div class="relative z-10 w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800"
         x-trap.inert.noscroll="active">

      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white">
          <?php echo ui_text(__('ui.scanner.open', [], null, 'Scan Barcode / QR')); ?>
        </h2>
        <button
          type="button"
          @click="closeScanner()"
          class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10"
          aria-label="<?php echo ui_attr(__('ui.scanner.close', [], null, 'Close')); ?>"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 6 18 18M6 18 18 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <p x-show="!error" class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        <?php echo ui_text(__('ui.scanner.scanning', [], null, 'Scanning…')); ?>
      </p>
      <p class="mb-3 small text-muted">
        <?php echo ui_text($lang === 'ja' ? 'Webカメラで読み取れない場合は、入力欄にカーソルを合わせてバーコードリーダー本体で読み取れます。' : 'If camera scanning is unavailable, focus the input field and scan with a hardware barcode reader.'); ?>
      </p>

      <div
        id="<?php echo htmlspecialchars($readerId, ENT_QUOTES, 'UTF-8'); ?>"
        class="overflow-hidden rounded-lg bg-black"
        style="min-height:250px;"
        x-show="!error"
      ></div>

      <div
        x-show="error"
        class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300"
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
