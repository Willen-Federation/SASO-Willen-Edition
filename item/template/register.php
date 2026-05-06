<?php $this->title = '商品登録'; ?>
<?php $this->content = function($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="./">ホーム</a></li>
<li class="breadcrumb-item active">商品登録</li>
</ol>
</nav>

<form method="post" action="./item/add/">
<p>商品名(50字以内)：<input type="text" name="itemName" size="50" maxlength="50" required value=""></p>
<p>分類：</p>
<div id="category">
<div id="appendingParentInputs"></div>
<button id="appendingParent">+</button>
<div id="categoriesRoot">
</div>
<p>選択中の分類：<span class="categoryPath categoryPathChangable"></span><button type="button" class="hidden" id="deselectCategory">選択解除</button></p>
</div>
<input type="hidden" name="categoryId" id="categoryId" value="">
<p>価格：<input type="text" name="price" pattern="^[0-9,]+$" maxlength="11" value="">※9桁までの数。<p>
<p>色：<input type="text" name="colorName" required value="">※ 複数入力する場合は半角カンマ( , )で区切って下さい。</p>
<p>サイズ：<input type="text" name="sizeName" required value="">※ 複数入力する場合は半角カンマ( , )で区切って下さい。</p>
<p>
各色・各サイズは50字まで。
<br>色の数とサイズの数をかけて100を超えてはいけません。
<br>色数×サイズ数 &le; 100
</p>
<p>梱包</p>
<p><input type="checkbox" name="pla" value="1">プラ<input type="text" name="plaNote" maxlength="50" value=""></p>
<p><input type="checkbox" name="paper" value="1">紙<input type="text" name="paperNote" maxlength="50" value=""></p>
<p><input type="submit" value="登録"></p>
</form>

<?php }; ?>
