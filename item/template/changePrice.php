<?php $this->content = function($v) { ?>

<h2>価格変更</h2>
<form method="post" action="./item/changePrice/item/<?php echo $v->item->id; ?>">
<p>価格：<input type="text" name="price" pattern="^[0-9,]+$" maxlength="11" value="<?php echo $v->itemVar->price; ?>">※9桁までの数<p>
<button>変更</button>
</form>

<?php }; ?>