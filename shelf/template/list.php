<?php $this->title = '棚番ラベル印刷'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item"><a href="./shelf/start/">棚番作成</a></li>
<li class="breadcrumb-item active">棚番ラベル印刷</li>
</ol>
</nav>

<p>以下の番号を印刷します：</p>

<?php if($v->pagesAmount != 1) { ?>
<input type="number" id="pageNumber" min="1" max="<?php echo $v->pagesAmount; ?>" value="<?php echo $v->page; ?>"> / <?php echo $v->pagesAmount; ?>
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
<button class="btn btn-primary" id="submitMultiButton">移動</button>

</p>
</form>
<?php } ?>

<p>
<?php
foreach($v->shelves as $shelf) {
    echo $shelf."<br>";
}
?>
</p>

<form id="labelPrint" target="_blank" method="post" action="./shelf/outputPdf/">
<p>ラベルを選択して下さい。</p>
<ul class="unstyled">
<?php ($v->inside)('label', 'list'); ?>
<ul>
<?php foreach($v->shelves as $index=>$shelf) { ?>
<input type="hidden" name="shelf<?php echo $index; ?>" value="<?php echo $shelf; ?>">
<?php } ?>
<input type="hidden" name="amount" value="<?php echo count($v->shelves); ?>">

<p><input type="submit" value="PDF出力"></p>
</form>

<?php ($v->inside)('label', 'svg'); ?>

<?php }; ?>
