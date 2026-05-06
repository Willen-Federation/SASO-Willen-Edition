<?php $this->title = '棚番ラベル印刷'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item"><a href="./shelf/start/">棚番作成</a></li>
  <li class="breadcrumb-item active" aria-current="page">棚番ラベル印刷</li>
</ol>

<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title">以下の番号を印刷します</h3>
  </div>
  <div class="card-body">

    <?php if($v->pagesAmount != 1) { ?>
      <div class="row g-2 align-items-center mb-3">
        <div class="col-auto">
          <div class="input-group">
            <input type="number" id="pageNumber" class="form-control" min="1"
                   max="<?php echo (int)$v->pagesAmount; ?>" value="<?php echo (int)$v->page; ?>" style="max-width: 6em;">
            <span class="input-group-text">/ <?php echo (int)$v->pagesAmount; ?></span>
          </div>
        </div>
        <div class="col-auto">
          <button type="button" class="btn btn-primary" id="submitMultiButton">
            <i class="bi bi-arrow-right me-1"></i>移動
          </button>
        </div>
      </div>
      <?php
        // Generate hidden inputs based on actual dimension count
        $dimensionMetadata = $v->dimensionMetadata ?? [];
        $dimensionCount = !empty($dimensionMetadata) ? count($dimensionMetadata) : count($v->mins);

        for ($i = 0; $i < $dimensionCount; $i++) {
          $dimNum = $i + 1;
          $minVal = is_numeric($v->mins[$i] ?? null) ? (int)$v->mins[$i] : htmlspecialchars($v->mins[$i] ?? '', ENT_QUOTES, 'UTF-8');
          $maxVal = $v->maxs[$i] ?? '';
      ?>
        <input type="hidden" id="dimension<?php echo $dimNum; ?>min" value="<?php echo $minVal; ?>">
        <input type="hidden" id="dimension<?php echo $dimNum; ?>max" value="<?php echo $maxVal; ?>">
      <?php
        }
      ?>>
    <?php } ?>

    <p class="font-monospace">
      <?php foreach($v->shelves as $shelf) { echo htmlspecialchars($shelf, ENT_QUOTES, 'UTF-8')."<br>"; } ?>
    </p>
  </div>
</div>

<form id="labelPrint" target="_blank" method="post" action="./shelf/outputPdf/">
  <div class="card mb-3">
    <div class="card-body">
      <p class="form-label">ラベルを選択してください。</p>
      <ul class="list-unstyled mb-3">
        <?php ($v->inside)('label', 'list'); ?>
      </ul>
      <?php foreach($v->shelves as $index=>$shelf) { ?>
        <input type="hidden" name="shelf<?php echo (int)$index; ?>" value="<?php echo htmlspecialchars($shelf, ENT_QUOTES, 'UTF-8'); ?>">
      <?php } ?>
      <input type="hidden" name="amount" value="<?php echo count($v->shelves); ?>">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-printer me-1"></i>PDF出力
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
