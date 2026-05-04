<?php $this->title = 'パスワード変更'; ?>
<?php $this->content = function ($v) { ?>

<nav aria-label="breadcrumb">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="/">ホーム</a></li>
<li class="breadcrumb-item active">パスワード変更</li>
</ol>
</nav>

<?php if ($v->changed) { ?>
<p class="text-success">パスワードが変更されました。</p>
<?php } ?>
<?php if ($v->errorNow) { ?>
<p class="text-error-500">現在のパスワードが正しくありません。</p>
<?php } ?>

<p>パスワードはどこかに書き留めておいて下さい。忘れると、復元できません。<p>
<form method="post" action="/start/password/">
<p>現在のパスワード：<input id="nowPassword" type="password" name="now" pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required><p>

<p>新しいパスワード：<input id="newPassword" type="password" name="new" pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required>（半角英数、8〜20文字）<p>
<p>新しいパスワード確認：<input id="confirmPassword" type="password" name="confirm" pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required><p>
<p id="confirmPasswordError" class="hidden">パスワードが一致しません。</p>
<p><input id="changePasswordSubmit" type="submit" value="パスワード変更" disabled><p>
</form>

<?php } ?>