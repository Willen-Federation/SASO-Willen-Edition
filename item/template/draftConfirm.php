<?php $this->title = __('ui.item.draft_confirm.title', [], null, 'Confirm Draft Item'); ?>
<?php $this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $draft    = $v->draft ?? [];
    $draftId  = (int) ($draft['id'] ?? 0);
    $aiResult = $draft['ai_result'] ?? [];
    $userData = $draft['user_data'] ?? [];
    $imagePath = $draft['image_path'] ?? '';

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
      <div class="row g-4">

        <!-- Left: Product image -->
        <div class="col-lg-6">
          <div class="d-flex flex-column align-items-center gap-3">
            <?php if ($imagePath): ?>
              <img src="./<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                   alt="<?php echo $lang === 'ja' ? '商品画像' : 'Product image'; ?>"
                   class="img-fluid rounded border"
                   style="max-height:400px;object-fit:contain;">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center rounded border bg-light"
                   style="width:100%;max-width:20rem;height:16rem;">
                <i class="bi bi-image text-muted" style="font-size:4rem;" aria-hidden="true"></i>
              </div>
            <?php endif; ?>

            <?php if (!empty($aiResult)): ?>
              <div class="alert alert-info w-100" role="note" style="max-width:20rem;">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="bi bi-lightbulb fs-5" aria-hidden="true"></i>
                  <span class="fw-semibold"><?php echo $lang === 'ja' ? 'AI解析済み' : 'AI Analysed'; ?></span>
                </div>
                <p class="small mb-0">
                  <?php echo $lang === 'ja'
                    ? 'AI が提案した値は下のフォームに事前入力されています。必要に応じて修正してください。'
                    : 'AI-suggested values are pre-filled in the form. Edit as needed before confirming.'; ?>
                </p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: Confirmation form -->
        <div class="col-lg-6">
          <form id="draft-discard-form" method="post"
                action="./item/draftDiscard/id/<?php echo $draftId; ?>/"></form>

          <form method="post" action="./item/draftSave/id/<?php echo $draftId; ?>/" novalidate>

            <?php
            $aiBadge = '<span class="badge bg-info-subtle text-info ms-2 small">'
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
                echo '<div class="mb-3">';
                echo '<label for="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-label">';
                echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
                if ($required) {
                    echo '<span class="text-danger ms-1" aria-hidden="true">*</span>';
                }
                echo $badge;
                echo '</label>';
                if ($type === 'textarea') {
                    echo '<textarea id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                        . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                        . ' rows="3"'
                        . ' class="form-control"'
                        . ($required ? ' required' : '')
                        . '>' . htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8') . '</textarea>';
                } else {
                    echo '<input type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"'
                        . ' id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                        . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
                        . ' value="' . htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8') . '"'
                        . ' class="form-control"'
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
              <div class="mb-3">
                <p class="form-label">
                  <?php echo $lang === 'ja' ? 'カテゴリヒント' : 'Category Hint'; ?>
                  <span class="badge bg-info-subtle text-info ms-2 small">
                    <?php echo $lang === 'ja' ? 'AI提案' : 'AI Suggestion'; ?>
                  </span>
                </p>
                <div class="border rounded p-3 bg-light small text-muted">
                  <?php echo htmlspecialchars($categoryHint, ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between gap-3 pt-2">
              <button type="submit" form="draft-discard-form" class="btn btn-danger"
                      onclick="return confirm(<?php echo htmlspecialchars(json_encode($lang === 'ja' ? 'このドラフトを破棄しますか？' : 'Discard this draft?'), ENT_QUOTES, 'UTF-8'); ?>)">
                <?php echo $lang === 'ja' ? '破棄する' : 'Discard'; ?>
              </button>

              <div class="d-flex align-items-center gap-2">
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

      </div>
<?php
    },
  ]);
?>

<?php }; ?>
