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
                   max="<?php echo $v->pagesAmount; ?>" value="<?php echo $v->page; ?>" style="max-width: 6em;">
            <span class="input-group-text">/ <?php echo $v->pagesAmount; ?></span>
          </div>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary" id="submitMultiButton">
            <i class="ti ti-arrow-right me-1"></i>移動
          </button>
        </div>
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
    <?php } ?>

    <p class="font-monospace">
      <?php foreach($v->shelves as $shelf) { echo $shelf."<br>"; } ?>
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
        <input type="hidden" name="shelf<?php echo $index; ?>" value="<?php echo $shelf; ?>">
      <?php } ?>
      <input type="hidden" name="amount" value="<?php echo count($v->shelves); ?>">
      <button type="submit" class="btn btn-primary">
        <i class="ti ti-printer me-1"></i>PDF出力
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
