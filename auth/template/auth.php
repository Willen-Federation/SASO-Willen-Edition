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

<?php if ($v->providers !== []) { ?>
<hr>
<p>外部サービスでログイン：</p>
<div style="display: flex; flex-direction: column; gap: 10px;">
<?php foreach ($v->providers as $p) { ?>
    <a href="./auth/start/<?php echo $p->id->value; ?>" style="display: block; padding: 10px; border: 1px solid #ccc; border-radius: 5px; text-decoration: none; text-align: center; background: #f9f9f9; color: #333;">
        <?php echo htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8'); ?> でログイン
    </a>
<?php } ?>
</div>
<?php } ?>

<?php }; ?>
