<?php $this->title = '商品ラベル印刷'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">商品ラベル印刷</li>
</ol>

<div class="card mb-3">
  <div class="card-header">
    <h3 class="card-title">以下の商品ラベルを印刷します</h3>
    <div class="card-actions">
      <form method="post" action="./label/deleteAll/" class="m-0">
        <input type="hidden" name="deleteAll" value="deleteAll">
        <button id="deleteAllItemLabels" type="button" class="btn btn-outline-warning btn-sm">
          <i class="ti ti-trash me-1"></i>商品ラベル全削除
        </button>
      </form>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-vcenter table-hover card-table">
      <thead>
        <tr>
          <th scope="col">商品情報</th>
          <th scope="col">商品詳細番号</th>
          <th scope="col" class="text-end">ラベル枚数</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($v->labelCaches as $labelCache) { if($labelCache) { ?>
        <tr>
          <td>
            <a href="./item/start/item/<?php echo $labelCache->feature->item->id; ?>/color/<?php echo $labelCache->feature->color->code; ?>/size/<?php echo $labelCache->feature->size->code; ?>/action/label">
              <span id="longName<?php echo $labelCache->feature->getFullCode(); ?>"><?php
                echo $labelCache->feature->item->name; ?>/<?php echo $labelCache->feature->color->name; ?>(<?php echo $labelCache->feature->color->code; ?>)<?php echo $labelCache->feature->size->name; ?></span>
            </a>
          </td>
          <td><span class="fullCode font-monospace"><?php echo $labelCache->feature->getFullCode(); ?></span></td>
          <td class="text-end"><?php echo $labelCache->amount; ?></td>
        </tr>
      <?php } } ?>
      </tbody>
    </table>
  </div>
</div>

<form id="labelPrint" target="_blank" method="post" action="./label/outputPdf/">
  <div class="card mb-3">
    <div class="card-body">
      <p class="form-label">ラベルを選択してください。</p>
      <ul class="list-unstyled mb-3">
        <?php ($v->inside)('label', 'list'); ?>
      </ul>
      <button type="submit" class="btn btn-primary">
        <i class="ti ti-printer me-1"></i>PDF出力
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
