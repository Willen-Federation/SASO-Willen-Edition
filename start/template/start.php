<?php $this->title = 'ホーム'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<!-- Quick Action Cards -->
<div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
  <a href="./item/add/" class="card flex flex-col items-center gap-3 p-6 text-center hover:shadow-4 transition group">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary bg-opacity-10 group-hover:bg-primary transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    </div>
    <span class="text-sm font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? '商品登録' : 'Add Product'; ?></span>
  </a>
  <a href="./barcode/sheet/" class="card flex flex-col items-center gap-3 p-6 text-center hover:shadow-4 transition group">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-meta-3 bg-opacity-10 group-hover:bg-meta-3 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-meta-3 group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
    </div>
    <span class="text-sm font-medium text-black dark:text-white">
      <?php echo $lang === 'ja' ? 'バーコード印刷' : 'Print Barcodes'; ?>
      <span class="ml-1 badge badge-primary text-xs">NEW</span>
    </span>
  </a>
  <a href="./shelf/simple/" class="card flex flex-col items-center gap-3 p-6 text-center hover:shadow-4 transition group">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning bg-opacity-10 group-hover:bg-warning transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-warning group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
    </div>
    <span class="text-sm font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? '棚番設定' : 'Shelf Setup'; ?></span>
  </a>
  <a href="./verify/start/" class="card flex flex-col items-center gap-3 p-6 text-center hover:shadow-4 transition group">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-meta-5 bg-opacity-10 group-hover:bg-meta-5 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-meta-5 group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    </div>
    <span class="text-sm font-medium text-black dark:text-white">
      <?php echo $lang === 'ja' ? 'データ照合' : 'Data Verify'; ?>
      <span class="ml-1 badge badge-primary text-xs">NEW</span>
    </span>
  </a>
</div>

<!-- Barcode Scanner + Item List -->
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
  <!-- Barcode scanner (1/3) -->
  <div class="card">
    <div class="card-header flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
      <h2 class="text-lg font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'バーコードスキャン' : 'Barcode Scan'; ?></h2>
    </div>
    <div class="card-body">
      <?php ($v->inside)('barcode', 'start'); ?>
    </div>
  </div>

  <!-- Item list (2/3) -->
  <div class="card lg:col-span-2">
    <div class="card-header flex items-center justify-between">
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <h2 class="text-lg font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '商品一覧' : 'Product List'; ?></h2>
      </div>
      <a href="./item/add/" class="btn-primary btn-sm text-sm px-4 py-2"><?php echo $lang === 'ja' ? '+ 追加' : '+ Add'; ?></a>
    </div>
    <div class="card-body p-0 overflow-x-auto">
      <?php ($v->inside)('item', 'listFrame'); ?>
    </div>
  </div>
</div>

<?php }; ?>
