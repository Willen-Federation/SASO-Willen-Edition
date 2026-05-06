<?php $this->title = '色・サイズ追加'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item"><a href="./item/start/item/<?php echo $v->item->id; ?>">商品情報</a></li>
<li class="breadcrumb-item active">色・サイズ追加</li>
</ol>
</nav>

<table class="table table-striped">
<?php ($v->inside)('item', 'head'); ?>
<?php ($v->inside)('item', 'row', $v->item); ?>
</table>

<form method="post" action="./item/addFeature/item/<?php echo $v->item->id; ?>">
<p>追加する色：<input type="text" name="colorName">
<p>追加するサイズ：<input type="text" name="sizeName">
<br>※ 追加するものが複数ある場合は半角カンマ（ , ）で区切って下さい。</p>
<p>
各色・各サイズは50字まで。
<br>色の数とサイズの数をかけて100を超えてはいけません。
<br>色数×サイズ数 &le; 100
</p>
<p><button>追加</button></p>
</form>

<?php }; ?>
