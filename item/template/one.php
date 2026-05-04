<?php $this->title = '商品情報'; ?>
<?php $this->content = function($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="./">ホーム</a></li>
  <?php if($v->archive->archive) { ?>
    <li class="breadcrumb-item"><a href="./archive/list/">アーカイブ一覧</a></li>
  <?php } ?>
  <li class="breadcrumb-item active" aria-current="page">商品情報</li>
</ol>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-striped table-vcenter card-table"
           aria-label="商品情報">
      <?php ($v->inside)('item', 'head'); ?>
      <?php ($v->inside)('item', 'row', $v->item); ?>
    </table>
  </div>
</div>

<?php if(!$v->archive->archive) { ?>
  <div class="btn-list mb-3">
    <a href="./item/edit/item/<?php echo $v->item->id; ?>" class="btn btn-outline-primary">
      <i class="ti ti-edit me-1"></i>商品情報編集
    </a>
    <a href="./item/addFeature/item/<?php echo $v->item->id; ?>" class="btn btn-outline-primary">
      <i class="ti ti-plus me-1"></i>色・サイズ追加
    </a>
  </div>
<?php } else { ?>
  <div class="card mb-3">
    <div class="card-body">
      <dl class="row mb-3">
        <dt class="col-sm-3">アーカイブ理由</dt>
        <dd class="col-sm-9"><?php echo htmlspecialchars($v->archive->archiveNote, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt class="col-sm-3">アーカイブ日時</dt>
        <dd class="col-sm-9"><?php echo $v->archive->archiveAt->format('Y年m月d日 H時i分') ?></dd>
      </dl>
      <form method="post" action="<?php echo './item/reproduction/item/' . $v->item->id; ?>">
        <input type="hidden" name="isPost" value="true">
        <button type="submit" class="btn btn-warning"><i class="ti ti-refresh me-1"></i>復刻</button>
      </form>
    </div>
  </div>
<?php } ?>

<h2 class="h2 mb-3">数量・棚番管理</h2>
<table class="table table-striped table-hover table-vcenter" aria-label="数量・棚番管理">
<thead><tr>
<th scope="col">商品詳細番号</th>
<th scope="col">色</th>
<th scope="col">サイズ</th>
<th scope="col">数量</th>
<?php if(!$v->archive->archive) { ?>
<th scope="col">入庫</th>
<th scope="col">出庫</th>
<th scope="col">棚卸
<div class="form-check form-switch">
    <input type="checkbox" class="form-check-input" id="inventoryButtonDisplayButton">
    <label class="form-check-label" for="inventoryButtonDisplayButton">許可</label>
</div>
</th>
<?php } ?>
<th scope="col">棚番</th>
<th scope="col">ラベル枚数</th>
</tr></thead><tbody>
<?php
foreach($v->quantityLogsGen as $quantityLogs) {
    $feature = $quantityLogs->feature;
?>
<tr>
<td class="featureCode">
<?php if($quantityLogs->isInventoried()){ ?>
<a href="<?php echo './item/history/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<?php echo $feature->getFullCode(); ?>
</a>
<?php }else{ echo $feature->getFullCode(); } ?>
</td>
<td><a href="<?php echo './image/start/item/' . $feature->item->id . '/color/' . htmlspecialchars($feature->color->code, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($feature->color->name, ENT_QUOTES, 'UTF-8') . '(' . htmlspecialchars($feature->color->code, ENT_QUOTES, 'UTF-8') . ')'; ?></a></td>
<td><?php echo htmlspecialchars($feature->size->name, ENT_QUOTES, 'UTF-8'); ?></td>
<td class="number featureSum" id="sumof<?php echo $feature->getFullCode(); ?>">
<?php if($quantityLogs->isInventoried()){ echo $quantityLogs->sum() ;} ?>
</td>
<?php if(!$v->archive->archive) { ?>
<?php if($quantityLogs->isInventoried()){ ?>
<td>
<form method="post" action="<?php echo './item/stock/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<div class="input-group mb-3">
    <input
        type="number" name="amount"
        class="form-control <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='stock')?'focused':''; ?>"
        aria-describedby="stockButton" max="9999" min="1" required
    > 
    <input type="hidden" name="kind" value="stock">
    <button type="submit" class="btn btn-outline-primary stockButton" id="stockButton">入庫</button>
</div>
</form>
</td>
<td>
<form method="post" action="<?php echo './item/shipment/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<div class="input-group mb-3">
    <input
        id="shipmentof<?php echo $feature->getFullCode(); ?>" type="number" name="amount"
        class="form-control <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='shipment')?'focused':''; ?>"
        aria-describedby="shipmentButton" max="9999" min="1" required
    > 
    <input type="hidden" name="kind" value="shipment">
    <button type="submit" class="btn btn-outline-primary shipmentButton" id="shipmentButton">出庫</button>
</div>
</form>
</td>
<td>
<form method="post" action="<?php echo './item/inventory/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<div class="input-group mb-3">
    <input
        type="number" name="amount"
        class="form-control <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='inventory')?'focused':''; ?>"
        aria-describedby="inventoryButton" max="9999" min="0" required
    > 
    <input type="hidden" name="kind" value="inventory">
    <button type="submit" class="btn btn-outline-primary inventoryButton" id="inventoryButton" disabled>棚卸</button>
</div>
</form>
</td>
<?php }else{ ?>
<td></td>
<td></td>
<td>
<form method="post" action="<?php echo './item/inventory/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<div class="input-group mb-3">
    <input
        type="number" name="amount"
        class="form-control <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='inventory')?'focused':''; ?>"
        aria-describedby="inventoryButton" max="9999" min="0" required
    > 
    <input type="hidden" name="kind" value="inventory">
    <button type="submit" class="btn btn-outline-primary inventoryButton" id="inventoryButton">棚卸</button>
</div>
</form>
</td>
<?php } ?>
<?php } ?>
<td>
<form method="post" action="<?php echo './shelf/put/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<div class="input-group mb-3">
    <input
        type="text" name="number" value="<?php echo htmlspecialchars($feature->shelf?->number ?? '', ENT_QUOTES, 'UTF-8'); ?>"
        class="form-control <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='shelf')?'focused':''; ?>"
        aria-describedby="putShelfButton" pattern="^[0-9A-Za-z\-]+$" maxlength="15" required
    >
    <button type="submit" class="btn btn-outline-primary" id="putShelfButton">棚置</button>
</div>
</form>
</td>
<td>
<form method="post" action="<?php echo './label/select/item/'.$feature->item->id.'/color/'.$feature->color->code.'/size/'.$feature->size->code; ?>">
<div class="input-group mb-3">
    <input
        type="number" name="amount" value="<?php echo $feature->labelAmount===0?'':$feature->labelAmount; ?>"
        class="form-control labelSheetsInput <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='label')?'focused':''; ?>"
        aria-describedby="putShelfButton" min="0" max="100" range="1"
    >
    <button type="submit" class="btn btn-outline-primary" id="">追加</button>
</div>
</form>
</td>
</tr>
<?php
}
?>
</tbody></table>

<div id="labelSheetsAmount" class="d-none"><?php echo $v->labelSheetsAmount; ?></div>
<div id="labelSheetsAmountMax" class="d-none"><?php echo $v->labelSheetsAmountMax; ?></div>

<?php }; ?>