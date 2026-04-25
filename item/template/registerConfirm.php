<?php $this->title = '商品登録確認'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">商品登録</li>
</ol>
</nav>

<table class="table table-striped">
<tr>
<td>商品番号</td>
<td>商品名</td>
<td>分類</td>
<td>価格</td>
<td>プラ</td>
<td>役割名</td>
<td>紙</td>
<td>役割名</td>
<td>登録日</td>
<td>更新日</td>
<td>色</td>
<td>サイズ</td>
</tr>
<tr>
<td>-</td>
<td><?php echo $v->item->name; ?></td>
<td class="categoryPath categoryPathChangable"><?php echo $v->itemVar->categoryId; ?></td>
<td><?php echo number_format($v->itemVar->price??0); ?></td>
<td>
<?php if($v->item->pla){ ?>
<img src="./img/pla.gif" alt="プラ" width="25" height="25">
<?php } ?>
</td>
<td><?php echo $v->item->plaNote; ?></td>
<td>
<?php if($v->item->paper){ ?>
<img src="./img/kami.gif" alt="紙" width="25" height="25">
<?php } ?>
</td>
<td><?php echo $v->item->paperNote; ?></td>
<td>-</td>
<td>- </td>
<td><?php echo $v->serializedColors; ?></td>
<td><?php echo $v->serializedSizes; ?></td>
</table>

<form method="post" action="./item/add/">
<input type="hidden" name="itemNameConfirm" value="<?php echo $v->item->name; ?>">
<input type="hidden" name="categoryIdConfirm" value="<?php echo $v->itemVar->categoryId; ?>">
<input type="hidden" name="priceConfirm" value="<?php echo $v->itemVar->price; ?>">
<input type="hidden" name="colorNameConfirm" value="<?php echo $v->validFeaturesAmount?$v->inputColors:''; ?>">
<input type="hidden" name="sizeNameConfirm" value="<?php echo $v->validFeaturesAmount?$v->inputSizes:''; ?>">
<input type="hidden" name="plaConfirm" value="<?php echo $v->item->pla?'1':''; ?>">
<input type="hidden" name="plaNoteConfirm" value="<?php echo $v->item->plaNote; ?>">
<input type="hidden" name="paperConfirm" value="<?php echo $v->item->paper?'1':''; ?>">
<input type="hidden" name="paperNoteConfirm" value="<?php echo $v->item->paperNote; ?>">
<p><input type="submit" value="登録"></p>

<?php
if(!$v->validFeaturesAmount) {
?>
<p class="alert alert-warning">
色の数とサイズの数をかけて100を超えてはいけません。
<br>色数×サイズ数 &le; 100
</p>
<?php
}
?>

<p>商品名(50字以内)：<input type="text" name="itemName" size="50" maxlength="50" required value="<?php echo $v->item->name; ?>"></p>
<p>分類：</p>
<div id="category">
<div id="appendingParentInputs"></div>
<button id="appendingParent">+</button>
<div id="categoriesRoot">
</div>
<p>選択中の分類：<span class="categoryPath categoryPathChangable"><?php echo $v->itemVar->categoryId; ?></span><button type="button" class="hidden" id="deselectCategory">選択解除</button></p>
</div>
<input type="hidden" name="categoryId" id="categoryId" value="<?php echo $v->itemVar->categoryId; ?>">
<p>価格：<input type="text" name="price" pattern="^[0-9,]+$" maxlength="11" value="<?php echo $v->itemVar->price; ?>">※9桁までの数。<p>
<p>色：<input type="text" name="colorName" required value="<?php echo $v->inputColors; ?>">※ 複数入力する場合は半角カンマ( , )で区切って下さい。</p>
<p>サイズ：<input type="text" name="sizeName" required value="<?php echo $v->inputSizes; ?>">※ 複数入力する場合は半角カンマ( , )で区切って下さい。</p>
<p>
各色・各サイズは50字まで。
<br>色の数とサイズの数をかけて100を超えてはいけません。
<br>色数×サイズ数 &le; 100
</p>
<p>梱包</p>
<p><input type="checkbox" name="pla" value="1" <?php if($v->item->pla){echo 'checked';} ?>>プラ<input type="text" name="plaNote" maxlength="50" value="<?php echo $v->item->plaNote; ?>"></p>
<p><input type="checkbox" name="paper" value="1" <?php if($v->item->paper){echo 'checked';} ?>>紙<input type="text" name="paperNote" maxlength="50" value="<?php echo $v->item->paperNote; ?>"></p>
<p><input type="submit" value="登録"></p>
</form>

<?php }; ?>
