<?php $this->title = '商品ラベル印刷'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">商品ラベル印刷</li>
</ol>
</nav>

<p>以下の商品ラベルを印刷します：</p>
<table class="table table-striped table-hover">
<thead>
<tr>
<th scope="col">商品情報</th>
<th scope="col">商品詳細番号</th>
<th scope="col">ラベル枚数</th>
<tbody>
<?php
foreach($v->labelCaches as $labelCache) {
    if($labelCache) {
?>
<tr>
    <td>
        <a href="./item/start/item/<?php
            echo $labelCache->feature->item->id;
        ?>/color/<?php
            echo $labelCache->feature->color->code;
        ?>/size/<?php
            echo $labelCache->feature->size->code;
        ?>/action/label">
        <span id="longName<?php echo $labelCache->feature->getFullCode(); ?>"><?php
            echo $labelCache->feature->item->name;
        ?>/<?php
            echo $labelCache->feature->color->name;
        ?>(<?php
            echo $labelCache->feature->color->code;
        ?>)<?php
            echo $labelCache->feature->size->name;
        ?></span>
        </a>
    </td>
    <td>
    <span class="fullCode"><?php echo $labelCache->feature->getFullCode(); ?></span>
    </td>
    <td>
        <?php echo $labelCache->amount; ?>
    </td>
</tr>
<?php
    }
}
?>
</tbody>
</table>

<div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
<form method="post" action="./label/deleteAll/">
    <input type="hidden" name="deleteAll" value="deleteAll">
    <button class="btn btn-warning" id="deleteAllItemLabels" type="button">商品ラベル全削除</button>
</form>
</div>

<form id="labelPrint" target="_blank" method="post" action="./label/outputPdf/">
<p>ラベルを選択して下さい。</p>
<ul class="unstyled">
<?php ($v->inside)('label', 'list'); ?>
<ul>

<p><input type="submit" value="PDF出力"></p>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
