<?php $this->title = '色・サイズ追加確認'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item"><a href="./item/start/item/<?php echo $v->item->id; ?>">商品情報</a></li>
<li class="breadcrumb-item"><a href="./item/addFeature/item/<?php echo $v->item->id; ?>">色・サイズ追加</a></li>
<li class="breadcrumb-item active">色・サイズ追加確認</li>
</ol>
</nav>

<table class="table table-striped">
<?php ($v->inside)('item', 'head'); ?>
<?php ($v->inside)('item', 'row', $v->item); ?>
</table>

<?php
if(!$v->isValidAmount) {
?>
<p class="alert alert-warning">
追加後の色の数とサイズの数をかけて100を超えてはいけません。
<br>色数×サイズ数 &le; 100
</p>
<?php
}
?>

<?php if(!empty($v->inputColors)) { ?>
<p>追加する色：<?php echo $v->serializedColors; ?></p>
<?php } ?>
<?php if(!empty($v->inputSizes)) { ?>
<p>追加するサイズ：<?php echo $v->serializedSizes; ?></p>
<?php } ?>

<form method="post" action="./item/addFeature/item/<?php echo $v->item->id; ?>">
<input type="hidden" name="colorNameConfirm" value="<?php echo $v->isValidAmount?$v->inputColors:''; ?>">
<input type="hidden" name="sizeNameConfirm" value="<?php echo $v->isValidAmount?$v->inputSizes:''; ?>">
<p>追加する色：<input type="text" name="colorName" value="<?php echo $v->inputColors; ?>">
<p>追加するサイズ：<input type="text" name="sizeName" value="<?php echo $v->inputSizes; ?>">
<br>※ 追加するものが複数ある場合は半角カンマ（ , ）で区切って下さい。</p>
<p><button>追加</button></p>
</form>

<?php }; ?>
