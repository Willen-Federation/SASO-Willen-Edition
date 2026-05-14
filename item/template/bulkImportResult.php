<?php $this->title = '一括登録結果'; ?>
<?php $this->content = function ($v) { ?>

<div class="mx-auto max-w-3xl space-y-6">

  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h2 class="font-semibold" style="color:var(--saso-text)">登録結果</h2>
    </div>
    <div class="px-5 py-5">
      <div class="flex gap-8 mb-5">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo (int) $v->successCount; ?></div>
          <div class="text-sm mt-1" style="color:var(--saso-text-sub)">成功</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold text-red-600 dark:text-red-400"><?php echo (int) $v->errorCount; ?></div>
          <div class="text-sm mt-1" style="color:var(--saso-text-sub)">エラー</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold" style="color:var(--saso-text)"><?php echo (int) $v->successCount + (int) $v->errorCount; ?></div>
          <div class="text-sm mt-1" style="color:var(--saso-text-sub)">合計</div>
        </div>
      </div>
      <div class="flex gap-3">
        <a href="./item/bulkImport/" class="btn btn-secondary btn-sm">← 一括操作に戻る</a>
        <?php if ($v->successCount > 0): ?>
        <a href="./start/start/" class="btn btn-primary btn-sm">トップへ</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($v->results)): ?>
  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h3 class="font-semibold" style="color:var(--saso-text)">詳細</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="ta-table">
        <thead>
          <tr>
            <th scope="col" class="w-16">行</th>
            <th scope="col">商品名</th>
            <th scope="col" class="w-24">結果</th>
            <th scope="col">商品番号 / エラー内容</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($v->results as $r): ?>
          <tr>
            <td class="text-center text-sm" style="color:var(--saso-text-sub)">
              <?php echo (int) $r['row']; ?>
            </td>
            <td class="text-sm" style="color:var(--saso-text)">
              <?php echo htmlspecialchars((string) ($r['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td class="text-center">
              <?php if ($r['status'] === 'success'): ?>
              <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                ✓ 成功
              </span>
              <?php else: ?>
              <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                ✗ エラー
              </span>
              <?php endif; ?>
            </td>
            <td class="text-sm">
              <?php if ($r['status'] === 'success'): ?>
              <a href="./item/start/item/<?php echo htmlspecialchars((string) ($r['itemId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>/"
                 class="font-mono underline text-primary dark:text-indigo-400">
                <?php echo htmlspecialchars((string) ($r['itemId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <?php else: ?>
              <span class="text-red-600 dark:text-red-400">
                <?php echo htmlspecialchars((string) ($r['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
              </span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php }; ?>
