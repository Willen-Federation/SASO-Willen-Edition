<?php $this->title = '入出庫履歴'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<?php if($v->archive->archive) { ?>
<li class="breadcrumb-item"><a href="./archive/list">アーカイブ一覧</a></li>
<?php } ?>
<li class="breadcrumb-item"><a href="<?php echo 'item/start/item/' . $v->item->id; ?>">商品情報</a></li>
<li class="breadcrumb-item active">入出庫履歴</li>
</ol>
</nav>

<table class="table table-striped">
<?php ($v->inside)('item', 'head'); ?>
<?php ($v->inside)('item', 'row', $v->item); ?>
</table>

<dl>
    <dt>カラー</dt>
    <dd><?php echo $v->color->name ?>(<?php echo $v->color->code ?>)</dd>
    <dt>サイズ</dt>
    <dd><?php echo $v->size->name ?></dd>
</dl>

<div class="span6">
<table class="table table-striped table-hover">
<tr>
<th>日時</th>
<th>入出庫数</th>
<th>摘要</th>
</tr>
<?php
foreach($v->quantityLogs as $log){
?>
<tr>
<td>
<?php echo $log->changeAt->format('Y年m月d日 H時i分'); ?>
</td>
<td class="number">
<?php echo $log->fluctuation; ?>
</td>
<td>
<?php
if($log->isInventory){
echo '棚卸';
}elseif($log->fluctuation < 0){
echo '出庫';
}else{
echo '入庫';
}
?>
</td>
</tr>
<?php } ?>
<tr>
<td><strong>合計</strong></td>
<td class="number">
<strong><?php echo $v->quantityLogs->sum(); ?></strong>
</td>
<td></td>
</tr>
</table>
</div>

<?php }; ?>
