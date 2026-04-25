<?php $this->title = '商品情報'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<?php if($v->archive->archive) { ?>
<li class="breadcrumb-item"><a href="./archive/list/">アーカイブ一覧</a></li>
<?php } ?>
<li class="breadcrumb-item active">商品情報</li>
</ol>
</nav>

<table class="table table-striped">
<?php ($v->inside)('item', 'head'); ?>
<?php ($v->inside)('item', 'row', $v->item); ?>
</table>

<?php if(!$v->archive->archive) { ?>
<p>
<a href="./item/edit/item/<?php echo $v->item->id; ?>">商品情報編集</a>
|
<a href="./item/addFeature/item/<?php echo $v->item->id; ?>">色・サイズ追加</a>
</p>
<?php } else { ?>
<dl>
    <dt>アーカイブ理由</dt>
    <dd><?php echo $v->archive->archiveNote ?></dd>
    <dt>アーカイブ日時</dt>
    <dd><?php echo $v->archive->archiveAt->format('Y年m月d日 H時i分') ?></dd>
</dl>
<form method="post" action="<?php echo './item/reproduction/item/' . $v->item->id; ?>">
<input type="hidden" name="isPost" value="true">
<button>復刻</button>
</form>
<?php } ?>

<h2>数量・棚番管理</h2>
<table class="table table-striped table-hover">
<tr>
<th>商品詳細番号</th>
<th>色</th>
<th>サイズ</th>
<th>数量</th>
<?php if(!$v->archive->archive) { ?>
<th>入庫</th>
<th>出庫</th>
<th>棚卸
<div class="form-check form-switch">
    <input type="checkbox" class="form-check-input" id="inventoryButtonDisplayButton">
    <label class="form-check-label" for="inventoryButtonDisplayButton">許可</label>
</div>
</th>
<?php } ?>
<th>棚番</th>
<th>ラベル枚数</th>
</tr>
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
<td><a href="<?php echo './image/start/item/' . $feature->item->id . '/color/' . $feature->color->code; ?>"><?php echo $feature->color->name . '(' . $feature->color->code . ')'; ?></a></td>
<td><?php echo $feature->size->name; ?></td>
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
        type="text" name="number" value="<?php echo $feature->shelf?->number; ?>"
        class="form-control <?php echo ($feature->color->code===$v->color &&$feature->size->code===$v->size &&$v->action==='shelf')?'focused':''; ?>"
        aria-describedby="putShelfButton" pattern="^[0-9A-Za-z\-]+$" maxlength="15" required
    >
    <button type="submit" class="btn btn-outline-primary" id="putShelfButton">棚置</button>
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
</form>
</td>
</tr>
<?php
}
?>
</table>

<div id="labelSheetsAmount" class="hidden"><?php echo $v->labelSheetsAmount; ?></div>
<div id="labelSheetsAmountMax" class="hidden"><?php echo $v->labelSheetsAmountMax; ?></div>

<?php }; ?>