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
    $statusBadgeClasses = [
        'queued'     => 'badge bg-secondary',
        'processing' => 'badge bg-info',
        'ready'      => 'badge bg-success',
        'failed'     => 'badge bg-danger',
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
      <span class="badge bg-primary rounded-pill me-2"><?php echo (int) $count; ?></span>
      <?php ui('button', [
        'label'   => $lang === 'ja' ? '画像で登録' : 'Register via Image',
        'type'    => 'link',
        'href'    => './item/addFromImage/',
        'variant' => 'primary',
        'size'    => 'sm',
      ]); ?>
    <?php },
    'body' => function () use ($drafts, $lang, $statusLabels, $statusBadgeClasses) {
      if (empty($drafts)): ?>
        <div class="d-flex flex-column align-items-center gap-3 py-5 text-muted">
          <i class="bi bi-image-off" style="font-size:3rem;" aria-hidden="true"></i>
          <p class="small mb-0"><?php echo $lang === 'ja' ? '保留中のドラフトはありません。' : 'No pending drafts.'; ?></p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-vcenter" aria-label="<?php echo $lang === 'ja' ? 'ドラフト一覧' : 'Draft items'; ?>">
            <thead>
              <tr>
                <th scope="col"><?php echo $lang === 'ja' ? '画像' : 'Image'; ?></th>
                <th scope="col"><?php echo $lang === 'ja' ? 'ステータス' : 'Status'; ?></th>
                <th scope="col"><?php echo $lang === 'ja' ? 'バーコードヒント' : 'Barcode Hint'; ?></th>
                <th scope="col"><?php echo $lang === 'ja' ? '登録日時' : 'Created'; ?></th>
                <th scope="col"><?php echo $lang === 'ja' ? '操作' : 'Actions'; ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($drafts as $draft):
                $status = $draft['status'] ?? 'queued';
                $badgeClass = $statusBadgeClasses[$status] ?? $statusBadgeClasses['queued'];
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
              <tr>
                <td>
                  <?php if ($imagePath): ?>
                    <img src="./<?php echo $imagePath; ?>"
                         alt="<?php echo $lang === 'ja' ? 'ドラフト画像' : 'Draft image'; ?>"
                         class="rounded border"
                         style="width:3.5rem;height:3.5rem;object-fit:cover;"
                         loading="lazy">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center rounded border bg-light"
                         style="width:3.5rem;height:3.5rem;">
                      <i class="bi bi-image text-muted" aria-hidden="true"></i>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="<?php echo $badgeClass; ?> d-inline-flex align-items-center gap-1">
                    <?php if ($status === 'processing'): ?>
                      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </td>
                <td class="text-muted">
                  <?php echo $barcodeHint ?: '<span class="text-muted">—</span>'; ?>
                </td>
                <td class="text-muted text-nowrap">
                  <?php echo htmlspecialchars($createdFormatted, ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td>
                  <?php if ($status === 'ready'): ?>
                    <a href="./item/draftConfirm/id/<?php echo $draftId; ?>/"
                       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                      <?php echo $lang === 'ja' ? '確認する' : 'Review'; ?>
                      <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                  <?php elseif ($status === 'failed'): ?>
                    <form method="post" action="./item/draftRetry/id/<?php echo $draftId; ?>/" class="d-inline">
                      <input type="hidden" name="id" value="<?php echo $draftId; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i><?php echo $lang === 'ja' ? '再試行' : 'Retry'; ?>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="small text-muted"><?php echo $lang === 'ja' ? '処理待ち' : 'Pending…'; ?></span>
                  <?php endif; ?>
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
