<?php $this->title = '棚番ラベル印刷'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">ホーム</a></li>
    <li class="breadcrumb-item"><a href="./shelf/start/">棚番作成</a></li>
    <li class="breadcrumb-item active" aria-current="page">棚番ラベル印刷</li>
  </ol>
</nav>

<p class="mb-4 text-sm" style="color:var(--saso-text-sub)">以下の番号を印刷します：</p>

<?php if($v->pagesAmount != 1) { ?>
<div class="flex items-center gap-2 mb-4">
  <label for="pageNumber" class="text-sm" style="color:var(--saso-text-sub)">ページ：</label>
  <input type="number" id="pageNumber" min="1" max="<?php echo $v->pagesAmount; ?>" value="<?php echo $v->page; ?>"
         class="form-input w-24">
  <span class="text-sm" style="color:var(--saso-text-sub)">/ <?php echo $v->pagesAmount; ?></span>
</div>
<input type="hidden" id="dimension1min" value="<?php echo $v->mins[0]??''; ?>">
<input type="hidden" id="dimension1max" value="<?php echo $v->maxs[0]??''; ?>">
<input type="hidden" id="dimension2min" value="<?php echo $v->mins[1]??''; ?>">
<input type="hidden" id="dimension2max" value="<?php echo $v->maxs[1]??''; ?>">
<input type="hidden" id="dimension3min" value="<?php echo $v->mins[2]??''; ?>">
<input type="hidden" id="dimension3max" value="<?php echo $v->maxs[2]??''; ?>">
<input type="hidden" id="dimension4min" value="<?php echo $v->mins[3]??''; ?>">
<input type="hidden" id="dimension4max" value="<?php echo $v->maxs[3]??''; ?>">
<input type="hidden" id="dimension5min" value="<?php echo $v->mins[4]??''; ?>">
<input type="hidden" id="dimension5max" value="<?php echo $v->maxs[4]??''; ?>">
<button class="btn btn-primary mb-4" id="submitMultiButton">移動</button>
<?php } ?>

<div class="mb-4 text-sm font-mono" style="color:var(--saso-text)">
<?php
foreach($v->shelves as $shelf) {
    echo htmlspecialchars($shelf, ENT_QUOTES, 'UTF-8')."<br>";
}
?>
</div>

<form id="labelPrint" target="_blank" method="post" action="./shelf/outputPdf/">
  <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
  <div class="rounded-2xl border shadow-sm overflow-hidden" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="px-6 py-5">
      <p class="form-label mb-3">ラベルを選択してください。</p>
      <ul class="space-y-1 mb-4">
        <?php ($v->inside)('label', 'list'); ?>
      </ul>
      <?php foreach($v->shelves as $index=>$shelf) { ?>
        <input type="hidden" name="shelf<?php echo (int)$index; ?>" value="<?php echo htmlspecialchars($shelf, ENT_QUOTES, 'UTF-8'); ?>">
      <?php } ?>
      <input type="hidden" name="amount" value="<?php echo count($v->shelves); ?>">
      <button type="submit" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        PDF出力
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
