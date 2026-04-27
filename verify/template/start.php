<?php $this->title = 'データ照合'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
  $results      = $v->verifyResults ?? null;
  $lastChecked  = $v->lastChecked ?? null;
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'データ照合' : 'Data Verification'; ?></li>
  </ol>
</nav>

<!-- Summary cards -->
<div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
  <?php
  $stats = [
    ['label_ja' => '総商品数', 'label_en' => 'Total Items', 'value' => $v->totalItems ?? 0, 'color' => 'text-primary', 'bg' => 'bg-primary'],
    ['label_ja' => '照合済み', 'label_en' => 'Verified', 'value' => $v->verifiedCount ?? 0, 'color' => 'text-success', 'bg' => 'bg-success'],
    ['label_ja' => '差異あり', 'label_en' => 'Discrepancies', 'value' => $v->discrepancyCount ?? 0, 'color' => 'text-danger', 'bg' => 'bg-danger'],
    ['label_ja' => '未照合', 'label_en' => 'Not Verified', 'value' => $v->unverifiedCount ?? 0, 'color' => 'text-warning', 'bg' => 'bg-warning'],
  ];
  foreach($stats as $s): ?>
  <div class="card flex items-center gap-4 p-5">
    <div class="flex h-11 w-11 items-center justify-center rounded-full <?php echo $s['bg']; ?> bg-opacity-10">
      <span class="text-lg font-bold <?php echo $s['color']; ?>"><?php echo (int)$s['value']; ?></span>
    </div>
    <div>
      <span class="text-sm font-medium text-body dark:text-bodydark"><?php echo $lang === 'ja' ? $s['label_ja'] : $s['label_en']; ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Action buttons -->
<div class="mb-6 flex flex-wrap gap-3">
  <form method="post" action="./verify/run/">
    <button type="submit" class="btn-primary px-6">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      <?php echo $lang === 'ja' ? '照合を実行' : 'Run Verification'; ?>
    </button>
  </form>
  <?php if($results): ?>
  <a href="./verify/export/" class="btn-secondary px-6">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    <?php echo $lang === 'ja' ? 'CSVエクスポート' : 'Export CSV'; ?>
  </a>
  <?php endif; ?>
  <?php if($lastChecked): ?>
  <span class="flex items-center text-sm text-body dark:text-bodydark">
    <?php echo $lang === 'ja' ? '最終照合：' : 'Last verified: '; ?>
    <time datetime="<?php echo htmlspecialchars($lastChecked); ?>" class="ml-1 font-medium">
      <?php echo htmlspecialchars($lastChecked); ?>
    </time>
  </span>
  <?php endif; ?>
</div>

<!-- Filters -->
<div
  x-data="{ filter: 'all', search: '' }"
  class="card mb-6"
>
  <div class="card-body flex flex-wrap items-center gap-4">
    <div class="flex rounded border border-stroke dark:border-strokedark overflow-hidden" role="group" aria-label="<?php echo $lang === 'ja' ? '絞り込み' : 'Filter'; ?>">
      <?php
      $filters = [
        ['val' => 'all', 'ja' => 'すべて', 'en' => 'All'],
        ['val' => 'ok', 'ja' => '一致', 'en' => 'OK'],
        ['val' => 'discrepancy', 'ja' => '差異あり', 'en' => 'Discrepancy'],
        ['val' => 'unverified', 'ja' => '未照合', 'en' => 'Not Verified'],
      ];
      foreach($filters as $f): ?>
      <button type="button" @click="filter = '<?php echo $f['val']; ?>'"
        class="px-4 py-2 text-sm transition"
        :class="filter === '<?php echo $f['val']; ?>' ? 'bg-primary text-white' : 'bg-white dark:bg-boxdark text-body dark:text-bodydark hover:bg-gray-2 dark:hover:bg-meta-4'"
        :aria-pressed="(filter === '<?php echo $f['val']; ?>').toString()">
        <?php echo $lang === 'ja' ? $f['ja'] : $f['en']; ?>
      </button>
      <?php endforeach; ?>
    </div>
    <div class="relative flex-1 min-w-48">
      <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 h-5 w-5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input x-model="search" type="search" class="form-input pl-11 py-2.5" placeholder="<?php echo $lang === 'ja' ? '商品名・バーコードで検索...' : 'Search items/barcodes...'; ?>">
    </div>
  </div>

  <!-- Results table -->
  <div class="overflow-x-auto">
    <?php if($results): ?>
    <table class="data-table" aria-label="<?php echo $lang === 'ja' ? 'データ照合結果' : 'Verification Results'; ?>">
      <thead>
        <tr>
          <th class="pl-9"><?php echo $lang === 'ja' ? '商品名' : 'Product'; ?></th>
          <th><?php echo $lang === 'ja' ? 'SKU / バーコード' : 'SKU / Barcode'; ?></th>
          <th><?php echo $lang === 'ja' ? 'システム在庫' : 'System Stock'; ?></th>
          <th><?php echo $lang === 'ja' ? '実棚卸数' : 'Physical Count'; ?></th>
          <th><?php echo $lang === 'ja' ? '差異' : 'Diff'; ?></th>
          <th><?php echo $lang === 'ja' ? '棚番' : 'Shelf'; ?></th>
          <th><?php echo $lang === 'ja' ? 'ステータス' : 'Status'; ?></th>
          <th><?php echo $lang === 'ja' ? '操作' : 'Actions'; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($results as $r): ?>
        <tr
          x-show="
            (filter === 'all' || filter === '<?php echo htmlspecialchars($r['status']); ?>') &&
            (!search || '<?php echo addslashes($r['name'] ?? ''); ?>'.toLowerCase().includes(search.toLowerCase()) || '<?php echo addslashes($r['sku'] ?? ''); ?>'.toLowerCase().includes(search.toLowerCase()))
          "
        >
          <td class="pl-9 font-medium text-black dark:text-white"><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
          <td><code class="text-xs bg-gray-2 dark:bg-meta-4 px-2 py-0.5 rounded"><?php echo htmlspecialchars($r['sku'] ?? ''); ?></code></td>
          <td class="text-right font-mono"><?php echo (int)($r['systemStock'] ?? 0); ?></td>
          <td class="text-right font-mono">
            <form method="post" action="./verify/updateCount/" class="flex items-center justify-end gap-1">
              <input type="hidden" name="sku" value="<?php echo htmlspecialchars($r['sku'] ?? ''); ?>">
              <input type="number" name="physicalCount" value="<?php echo (int)($r['physicalCount'] ?? 0); ?>" min="0" max="99999"
                class="form-input w-20 py-1 text-right text-sm" aria-label="実棚卸数">
              <button type="submit" class="btn-primary btn-sm text-xs px-2 py-1" aria-label="更新">✓</button>
            </form>
          </td>
          <td class="text-right font-mono <?php echo ($r['diff'] ?? 0) != 0 ? 'text-danger font-bold' : 'text-success'; ?>">
            <?php
            $diff = ($r['diff'] ?? 0);
            echo ($diff > 0 ? '+' : '') . (int)$diff;
            ?>
          </td>
          <td><?php echo htmlspecialchars($r['shelf'] ?? '—'); ?></td>
          <td>
            <?php
            $st = $r['status'] ?? 'unverified';
            $badges = [
              'ok'           => ['badge-success', $lang === 'ja' ? '一致' : 'OK'],
              'discrepancy'  => ['badge-danger',  $lang === 'ja' ? '差異あり' : 'Discrepancy'],
              'unverified'   => ['badge-warning', $lang === 'ja' ? '未照合' : 'Not Verified'],
            ];
            $badge = $badges[$st] ?? $badges['unverified'];
            echo '<span class="badge ' . $badge[0] . '">' . $badge[1] . '</span>';
            ?>
          </td>
          <td>
            <a href="./item/start/item/<?php echo (int)($r['itemId'] ?? 0); ?>" class="text-sm text-primary hover:underline">
              <?php echo $lang === 'ja' ? '詳細' : 'Detail'; ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="flex flex-col items-center gap-3 py-16 text-body dark:text-bodydark">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-stroke dark:text-strokedark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
      <p class="font-medium"><?php echo $lang === 'ja' ? 'まだ照合を実行していません' : 'No verification run yet'; ?></p>
      <p class="text-sm"><?php echo $lang === 'ja' ? '「照合を実行」ボタンでデータ照合を開始します' : 'Click "Run Verification" to start'; ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php }; ?>
