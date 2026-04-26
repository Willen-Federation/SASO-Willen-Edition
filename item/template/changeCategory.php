<?php $this->content = function($v) { ?>

<h2>分類変更</h2>
<p><button class="btn btn-primary" id="changeCategotyOfAnItem">分類一覧表示</button></p>
<div class="hidden" id="category">
<div id="appendingParentInputs"></div>
<button id="appendingParent">+</button>
<div id="categoriesRoot">
</div>
<p>新しい分類：<span class="categoryPath categoryPathChangable"></span><button type="button" class="hidden" id="deselectCategory">選択解除</button></p>
<p>もとの分類：<span class="categoryPath"><?php echo $v->itemVar->categoryId; ?></span></p>
<form method="post" action="./item/changeCategory/item/<?php echo $v->item->id; ?>">
<input type="hidden" name="categoryId" id="categoryId" value="<?php echo $v->itemVar->categoryId; ?>">
<button>変更</button>
</form>
</div>

<?php }; ?>