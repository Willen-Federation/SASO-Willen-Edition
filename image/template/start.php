<?php $this->title = '商品画像'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<?php if($v->archive->archive) { ?>
<li class="breadcrumb-item"><a href="./archive/list">アーカイブ一覧</a></li>
<?php } ?>
<li class="breadcrumb-item"><a href="<?php echo 'item/start/item/' . $v->item->id; ?>">商品情報</a></li>
<li class="breadcrumb-item active">商品画像</li>
</ol>
</nav>

<table class="table table-striped">
<?php ($v->inside)('item', 'head'); ?>
<?php ($v->inside)('item', 'row', $v->item); ?>
</table>

<p>
<?php echo $v->color->name . '(' . $v->color->code . ')'; ?>
</p>

<?php if(is_null($v->color->imageType)) { ?>
<p>画像はありません。</p>
<?php }else{ ?>
<img src="./image/display<?php echo  '/item/'.$v->item->id. '/color/' . $v->color->code; ?>" alt="<?php echo $v->item->name . 'の' .$v->color->name . '(' . $v->color->code . ')'; ?>">
<?php } ?>
<form method="post" action="./image/add<?php echo  '/item/'.$v->item->id. '/color/' . $v->color->code; ?>" enctype="multipart/form-data">
<input type="file" name="image">
<input type="submit" value="アップロード">
</form>
<p>画像形式はjpeg, png, gifのみ。</p>

<?php }; ?>