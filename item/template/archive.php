<?php $this->content = function($v) { ?>

<h2>アーカイブ</h2>
<form method="post" action="./item/archive/item/<?php echo $v->item->id; ?>">
<p>アーカイブ理由:
<input type="text" name="archiveNote" class="input" maxlength="50"></p>
<p><button>アーカイブ</button></p>
</form>

<?php }; ?>