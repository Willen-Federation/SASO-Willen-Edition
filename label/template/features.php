<?php $this->title = '商品ラベル印刷'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? '商品ラベル印刷' : 'Print Labels'; ?></li>
  </ol>
</nav>

<div class="card mb-6">
  <div class="card-header flex items-center justify-between">
    <h2 class="font-semibold text-black dark:text-white">
      <?php echo $lang === 'ja' ? '印刷対象ラベル一覧' : 'Labels to Print'; ?>
      <?php if(!empty($v->labelCaches)): ?>
      <span class="ml-2 badge badge-primary"><?php echo count(array_filter($v->labelCaches)); ?></span>
      <?php endif; ?>
    </h2>
    <form method="post" action="./label/deleteAll/" class="no-print">
      <input type="hidden" name="deleteAll" value="deleteAll">
      <button type="button" id="deleteAllItemLabels" class="btn-danger btn-sm text-sm px-4 py-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <?php echo $lang === 'ja' ? '全削除' : 'Clear All'; ?>
      </button>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="data-table" aria-label="<?php echo $lang === 'ja' ? 'ラベル一覧' : 'Label List'; ?>">
      <thead>
        <tr>
          <th class="pl-9"><?php echo $lang === 'ja' ? '商品情報' : 'Product'; ?></th>
          <th><?php echo $lang === 'ja' ? '商品詳細番号' : 'SKU'; ?></th>
          <th><?php echo $lang === 'ja' ? 'ラベル枚数' : 'Qty'; ?></th>
          <th class="no-print"><?php echo $lang === 'ja' ? '操作' : 'Actions'; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $hasAny = false;
        foreach($v->labelCaches as $labelCache) {
            if (!$labelCache) continue;
            $hasAny = true;
        ?>
        <tr>
          <td class="pl-9">
            <a href="./item/start/item/<?php echo $labelCache->feature->item->id; ?>/color/<?php echo $labelCache->feature->color->code; ?>/size/<?php echo $labelCache->feature->size->code; ?>/action/label"
               class="font-medium text-primary hover:underline" id="longName<?php echo $labelCache->feature->getFullCode(); ?>">
              <?php echo htmlspecialchars($labelCache->feature->item->name); ?>
              / <?php echo htmlspecialchars($labelCache->feature->color->name); ?>
              (<?php echo htmlspecialchars($labelCache->feature->color->code); ?>)
              <?php echo htmlspecialchars($labelCache->feature->size->name); ?>
            </a>
          </td>
          <td><code class="fullCode text-sm bg-gray-2 dark:bg-meta-4 px-2 py-0.5 rounded"><?php echo htmlspecialchars($labelCache->feature->getFullCode()); ?></code></td>
          <td>
            <span class="badge badge-primary"><?php echo (int)$labelCache->amount; ?></span>
          </td>
          <td class="no-print">
            <a href="./item/start/item/<?php echo $labelCache->feature->item->id; ?>/color/<?php echo $labelCache->feature->color->code; ?>/size/<?php echo $labelCache->feature->size->code; ?>/action/label"
               class="text-sm text-primary hover:underline">
              <?php echo $lang === 'ja' ? '変更' : 'Edit'; ?>
            </a>
          </td>
        </tr>
        <?php } ?>
        <?php if(!$hasAny): ?>
        <tr>
          <td colspan="4" class="py-10 text-center text-body dark:text-bodydark">
            <div class="flex flex-col items-center gap-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-stroke dark:text-strokedark" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              <p><?php echo $lang === 'ja' ? '印刷対象のラベルがありません。商品情報からラベル枚数を設定してください。' : 'No labels to print. Set label quantities from product details.'; ?></p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Print form -->
<form id="labelPrint" target="_blank" method="post" action="./label/outputPdf/" class="card no-print">
  <div class="card-header">
    <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'ラベルシートの選択' : 'Select Label Sheet'; ?></h2>
  </div>
  <div class="card-body">
    <div class="overflow-y-auto max-h-64">
      <?php ($v->inside)('label', 'list'); ?>
    </div>
    <div class="mt-4 flex gap-3">
      <button type="submit" class="btn-primary px-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <?php echo $lang === 'ja' ? 'PDF出力' : 'Export PDF'; ?>
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
