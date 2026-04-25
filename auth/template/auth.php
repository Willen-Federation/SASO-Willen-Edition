<?php $this->title = 'ログイン'; ?>
<?php $this->content = function($v) { ?>

<?php if($v->isError) { ?>
<p class="text-error">ID、パスワードが違います</p>
<?php } ?>
<form method="post" action="<?php echo './'.$v->restoredPath; ?>">
<p>ログインID：<input type="text" name="id"></p>
<p>パスワード：<input type="password" name="password"></p>
<p><input type="submit" value="ログイン"></p>
</form>

<?php }; ?>
