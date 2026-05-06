<?php $this->content = function($v) { ?>

<h2>サイズ表示順変更</h2>
<p>変更後の順番を数値で指定してください。(昇順)</p>
<form method="post" action="./item/changeSizeOrder/item/<?php echo $v->item->id; ?>">
<div class="container ms-0 w-50">
<?php foreach($v->sizes as $size){ ?>
<div class="row justify-content-start input-group mb-3">
    <span class="col-8 input-group-text"><?php echo $size->name; ?></span>
    <input type="number" class="col-4 form-control" name="size<?php echo $size->code; ?>" min="0" max="99" range="1" value="<?php echo $size->orderNumber; ?>">
</div>
<?php } ?>
</div>
<button>変更</button>
</form>

<?php }; ?>