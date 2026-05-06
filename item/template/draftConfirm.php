<?php $this->title = __('ui.item.draft_confirm.title', [], null, 'Confirm Draft Item'); ?>
<?php $this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $draft    = $v->draft ?? [];
    $draftId  = (int) ($draft['id'] ?? 0);
    $aiResult = $draft['ai_result'] ?? [];
    $userData = $draft['user_data'] ?? [];
    $imagePath = $draft['image_path'] ?? '';

    // Helper: prefer user_data over ai_result, show AI badge when from AI
    $fieldValue = function (string $aiKey, string $userKey = '') use ($aiResult, $userData): array {
        $uKey = $userKey ?: $aiKey;
        if (!empty($userData[$uKey])) {
            return ['value' => (string) $userData[$uKey], 'fromAi' => false];
        }
        if (!empty($aiResult[$aiKey])) {
            return ['value' => (string) $aiResult[$aiKey], 'fromAi' => true];
        }
        return ['value' => '', 'fromAi' => false];
    };

    $itemName    = $fieldValue('item_name', 'item_name');
    $janCode     = $fieldValue('jan_code',  'jan_code');
    $isbn        = $fieldValue('isbn',      'isbn');
    $manufacturer = $fieldValue('manufacturer');
    $description  = $fieldValue('description');
    $price        = $fieldValue('price',    'price');
    $categoryHint = $aiResult['category_hint'] ?? null;
?>

<?php
  ui('card', [
    'title' => $lang === 'ja' ? 'ドラフト商品の確認' : 'Confirm Draft Item',
    'body'  => function () use (
        $lang, $draftId, $imagePath,
        $itemName, $janCode, $isbn, $manufacturer, $description, $price, $categoryHint,
        $aiResult
    ) {
?>
      <div class="grid gap-8 lg:grid-cols-2">

        <!-- Left: Product image -->
        <div class="flex flex-col items-center gap-4">
          <?php if ($imagePath): ?>
            <img src="./<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="<?php echo $lang === 'ja' ? '商品画像' : 'Product image'; ?>"
                 class="w-full max-w-sm rounded-lg border border-gray-200 object-contain dark:border-gray-800"
                 style="max-height:400px;">
          <?php else: ?>
            <div class="flex h-64 w-full max-w-sm items-center justify-center rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-700">
              <svg class="h-16 w-16 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          <?php endif; ?>

          <?php if (!empty($aiResult)): ?>
            <div class="w-full max-w-sm rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/40 dark:bg-blue-900/20">
              <p class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-700 dark:text-blue-400">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <?php echo $lang === 'ja' ? 'AI解析済み' : 'AI Analysed'; ?>
              </p>
              <p class="text-xs text-blue-600 dark:text-blue-400">
                <?php echo $lang === 'ja'
                  ? 'AI が提案した値は下のフォームに事前入力されています。必要に応じて修正してください。'
                  : 'AI-suggested values are pre-filled in the form. Edit as needed before confirming.'; ?>
              </p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Right: Confirmation form -->
        <form method="post" action="./item/draftSave/id/<?php echo $draftId; ?>/" novalidate>

          <?php
          $aiBadge = '<span class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">'
            . ($lang === 'ja' ? 'AI提案' : 'AI Suggestion')
            . '</span>';

          $fieldHtml = function (
              string $name,
              string $labelJa,
              string $labelEn,
              array  $field,
              string $type = 'text',
              bool   $required = false
          ) use ($lang, $aiBadge) {
              $label = $lang === 'ja' ? $labelJa : $labelEn;
              $badge = $field['fromAi'] ? $aiBadge : '';
              $inputClass = 'w-full rounded border border-gray-200 bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white';
              echo '<div class="mb-4">';
              echo '<label for="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="mb-2.5 block font-medium text-black dark:text-white">';
              echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
              if ($required) {
                  echo '<span class="text-error-500 ml-1" aria-hidden="true">*</span>';
              }
              echo $badge;
              echo '</label>';
              if ($type === 'textarea') {
                  echo '<textarea id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                      . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                      . ' rows="3"'
                      . ' class="' . $inputClass . '"'
                      . ($required ? ' required' : '')
                      . '>' . htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8') . '</textarea>';
              } else {
                  echo '<input type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"'
                      . ' id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                      . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                      . ' value="' . htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8') . '"'
                      . ' class="' . $inputClass . '"'
                      . ($required ? ' required' : '')
                      . '>';
              }
              echo '</div>';
          };

          $fieldHtml('item_name',    '商品名',      'Product Name', $itemName,    'text', true);
          $fieldHtml('jan_code',     'JANコード',   'JAN Code',     $janCode);
          $fieldHtml('isbn',         'ISBN',        'ISBN',         $isbn);
          $fieldHtml('manufacturer', 'メーカー',    'Manufacturer', $manufacturer);
          $fieldHtml('price',        '価格',        'Price',        $price,       'text');
          $fieldHtml('description',  '説明',        'Description',  $description, 'textarea');
          ?>

          <?php if ($categoryHint): ?>
            <div class="mb-4">
              <p class="mb-2.5 block font-medium text-black dark:text-white">
                <?php echo $lang === 'ja' ? 'カテゴリヒント' : 'Category Hint'; ?>
                <span class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                  <?php echo $lang === 'ja' ? 'AI提案' : 'AI Suggestion'; ?>
                </span>
              </p>
              <p class="rounded border border-gray-200 bg-gray-100 px-5 py-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-700 dark:text-gray-400">
                <?php echo htmlspecialchars($categoryHint, ENT_QUOTES, 'UTF-8'); ?>
              </p>
            </div>
          <?php endif; ?>

          <div class="flex items-center justify-between gap-3 pt-2">
            <!-- Discard button -->
            <form method="post"
                  action="./item/draftDiscard/id/<?php echo $draftId; ?>/"
                  onsubmit="return confirm('<?php echo $lang === 'ja' ? 'このドラフトを破棄しますか？' : 'Discard this draft?'; ?>')">
              <?php ui('button', [
                'label'   => $lang === 'ja' ? '破棄する' : 'Discard',
                'type'    => 'submit',
                'variant' => 'danger',
              ]); ?>
            </form>

            <div class="flex items-center gap-3">
              <?php ui('button', [
                'label'   => $lang === 'ja' ? 'キャンセル' : 'Cancel',
                'type'    => 'link',
                'href'    => './item/drafts/',
                'variant' => 'secondary',
              ]); ?>
              <?php ui('button', [
                'label'   => $lang === 'ja' ? '商品として登録' : 'Confirm & Register',
                'type'    => 'submit',
                'variant' => 'primary',
              ]); ?>
            </div>
          </div>

        </form>

      </div>
<?php
    },
  ]);
?>

<?php }; ?>
