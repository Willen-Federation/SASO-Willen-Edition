<?php $this->title = __('ui.item.draft_list.title', [], null, 'Draft Items'); ?>
<?php $this->content = function ($v) {
    $lang = $_SESSION['lang'] ?? 'ja';
    $drafts = $v->drafts ?? [];
    $count = count($drafts);
    $flashSuccess = $_SESSION['flash_success'] ?? null;
    $flashError   = $_SESSION['flash_error']   ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);

    $statusLabels = [
        'queued'     => $lang === 'ja' ? '待機中'   : 'Queued',
        'processing' => $lang === 'ja' ? '処理中'   : 'Processing',
        'ready'      => $lang === 'ja' ? '確認待ち' : 'Ready',
        'failed'     => $lang === 'ja' ? '失敗'     : 'Failed',
    ];
    $statusClasses = [
        'queued'     => 'inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'processing' => 'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'ready'      => 'inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'failed'     => 'inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ];
?>

<?php if ($flashSuccess): ?>
  <?php ui('alert', ['variant' => 'success', 'body' => $flashSuccess, 'dismissible' => true]); ?>
<?php endif; ?>
<?php if ($flashError): ?>
  <?php ui('alert', ['variant' => 'danger', 'body' => $flashError, 'dismissible' => true]); ?>
<?php endif; ?>

<?php
  ui('card', [
    'title'   => ($lang === 'ja' ? '画像登録ドラフト' : 'Draft Items (Awaiting Confirmation)'),
    'actions' => function () use ($lang, $count) { ?>
      <span class="inline-flex items-center justify-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
        <?php echo (int) $count; ?>
      </span>
      <?php ui('button', [
        'label'   => $lang === 'ja' ? '画像で登録' : 'Register via Image',
        'type'    => 'link',
        'href'    => './item/addFromImage/',
        'variant' => 'primary',
        'size'    => 'sm',
      ]); ?>
    <?php },
    'body' => function () use ($drafts, $lang, $statusLabels, $statusClasses) {
      if (empty($drafts)): ?>
        <div class="flex flex-col items-center gap-3 py-12 text-gray-500 dark:text-gray-400">
          <svg class="h-12 w-12 text-gray-400 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <p class="text-sm font-medium"><?php echo $lang === 'ja' ? '保留中のドラフトはありません。' : 'No pending drafts.'; ?></p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full table-auto text-left text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700">
              <tr class="border-b border-gray-200 dark:border-gray-800">
                <th class="px-4 py-3 font-semibold text-black dark:text-white">
                  <?php echo $lang === 'ja' ? '画像' : 'Image'; ?>
                </th>
                <th class="px-4 py-3 font-semibold text-black dark:text-white">
                  <?php echo $lang === 'ja' ? 'ステータス' : 'Status'; ?>
                </th>
                <th class="px-4 py-3 font-semibold text-black dark:text-white">
                  <?php echo $lang === 'ja' ? 'バーコードヒント' : 'Barcode Hint'; ?>
                </th>
                <th class="px-4 py-3 font-semibold text-black dark:text-white">
                  <?php echo $lang === 'ja' ? '登録日時' : 'Created'; ?>
                </th>
                <th class="px-4 py-3 font-semibold text-black dark:text-white">
                  <?php echo $lang === 'ja' ? '操作' : 'Actions'; ?>
                </th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($drafts as $draft):
                $status = $draft['status'] ?? 'queued';
                $badgeClass = $statusClasses[$status] ?? $statusClasses['queued'];
                $label = $statusLabels[$status] ?? $status;
                $imagePath = htmlspecialchars($draft['image_path'] ?? '', ENT_QUOTES, 'UTF-8');
                $barcodeHint = htmlspecialchars($draft['barcode_hint'] ?? '', ENT_QUOTES, 'UTF-8');
                $createdAt = $draft['created_at'] ?? '';
                try {
                    $createdFormatted = (new \DateTime($createdAt))->format('Y-m-d H:i');
                } catch (\Exception $e) {
                    $createdFormatted = $createdAt;
                }
                $draftId = (int) ($draft['id'] ?? 0);
              ?>
              <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="px-4 py-3">
                  <?php if ($imagePath): ?>
                    <img src="./<?php echo $imagePath; ?>"
                         alt="<?php echo $lang === 'ja' ? 'ドラフト画像' : 'Draft image'; ?>"
                         class="h-14 w-14 rounded object-cover border border-gray-200 dark:border-gray-800"
                         loading="lazy">
                  <?php else: ?>
                    <div class="flex h-14 w-14 items-center justify-center rounded border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-700">
                      <svg class="h-6 w-6 text-gray-400 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                  <span class="<?php echo $badgeClass; ?>">
                    <?php if ($status === 'processing'): ?>
                      <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                  <?php echo $barcodeHint ?: '<span class="text-gray-400 dark:text-gray-300">—</span>'; ?>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                  <?php echo htmlspecialchars($createdFormatted, ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <?php if ($status === 'ready'): ?>
                      <a href="./item/draftConfirm/id/<?php echo $draftId; ?>/"
                         class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                        <?php echo $lang === 'ja' ? '確認する' : 'Review'; ?>
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                      </a>
                    <?php elseif ($status === 'failed'): ?>
                      <form method="post" action="./item/draftRetry/id/<?php echo $draftId; ?>/">
                        <input type="hidden" name="id" value="<?php echo $draftId; ?>">
                        <button type="submit"
                                class="inline-flex items-center gap-1 text-sm font-medium text-orange-600 hover:underline dark:text-orange-400">
                          <?php echo $lang === 'ja' ? '再試行' : 'Retry'; ?>
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-sm text-gray-400 dark:text-gray-500">
                        <?php echo $lang === 'ja' ? '処理待ち' : 'Pending…'; ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php },
  ]);
?>

<?php }; ?>
