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
        <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
        <input type="hidden" name="deleteAll" value="deleteAll">
        <button id="deleteAllItemLabels" type="button" class="btn btn-outline-warning btn-sm">
          <i class="bi bi-trash me-1"></i>商品ラベル全削除
        </button>
      </form>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-vcenter table-hover card-table"
           aria-label="印刷対象の商品ラベル一覧">
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
            <a href="./item/start/item/<?php echo (int)$labelCache->feature->item->id; ?>/color/<?php echo rawurlencode($labelCache->feature->color->code); ?>/size/<?php echo rawurlencode($labelCache->feature->size->code); ?>/action/label">
              <span id="longName<?php echo htmlspecialchars($labelCache->feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?>"><?php
                echo htmlspecialchars($labelCache->feature->item->name, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars($labelCache->feature->color->name, ENT_QUOTES, 'UTF-8'); ?>(<?php echo htmlspecialchars($labelCache->feature->color->code, ENT_QUOTES, 'UTF-8'); ?>)<?php echo htmlspecialchars($labelCache->feature->size->name, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
          </td>
          <td><span class="fullCode font-monospace"><?php echo htmlspecialchars($labelCache->feature->getFullCode(), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td class="text-end"><?php echo (int)$labelCache->amount; ?></td>
        </tr>
      <?php } } ?>
      </tbody>
    </table>
  </div>
</div>

<form id="labelPrint" target="_blank" method="post" action="./label/outputPdf/">
  <input type="hidden" name="csrftoken" value="<?php echo ui_attr(\saso\util\CSRFtoken::current()); ?>">
  <div class="card mb-3">
    <div class="card-body">
      <p class="form-label">ラベルを選択してください。</p>
      <ul class="list-unstyled mb-3">
        <?php ($v->inside)('label', 'list'); ?>
      </ul>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-printer me-1"></i>PDF出力
      </button>
    </div>
  </div>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
