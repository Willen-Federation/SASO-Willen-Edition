<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? '新規ユーザー登録' : 'Register New User';
$this->content = function ($v) {
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
?>
<ol class="breadcrumb mb-3">
  <li class="breadcrumb-item"><a href="./"><?php echo ui_text($lang === 'ja' ? 'ホーム' : 'Home'); ?></a></li>
  <li class="breadcrumb-item"><a href="./member/start/"><?php echo ui_text($lang === 'ja' ? 'ユーザー' : 'Users'); ?></a></li>
  <li class="breadcrumb-item active" aria-current="page"><?php echo ui_text($lang === 'ja' ? '登録' : 'Register'); ?></li>
</ol>

<div class="card" style="max-width:36rem;">
  <div class="card-header">
    <h3 class="card-title"><?php echo ui_text($lang === 'ja' ? 'ユーザー情報' : 'User Details'); ?></h3>
  </div>
  <form action="./member/add/" method="POST">
    <div class="p-6.5">
      <?php if (!empty($v->error)): ?>
        <div class="mb-5 text-error-500 font-medium"><?php echo htmlspecialchars($v->error); ?></div>
      <?php endif; ?>

      <div class="mb-3">
        <label for="m-id" class="form-label"><?php echo ui_text($lang === 'ja' ? 'ユーザーID' : 'User ID'); ?></label>
        <input id="m-id" type="text" name="id" class="form-control"
               placeholder="<?php echo ui_attr($lang === 'ja' ? '8〜20文字の英数字ID' : 'Enter alphanumeric User ID (8-20 chars)'); ?>"
               required minlength="8" maxlength="20" pattern="[a-zA-Z0-9_-]+">
      </div>

      <div class="mb-3">
        <label for="m-name" class="form-label"><?php echo ui_text($lang === 'ja' ? '名前' : 'Name'); ?></label>
        <input id="m-name" type="text" name="userName" class="form-control"
               placeholder="<?php echo ui_attr($lang === 'ja' ? '表示名を入力' : 'Enter display name'); ?>" required>
      </div>

      <div class="mb-3">
        <label for="m-pw" class="form-label"><?php echo ui_text($lang === 'ja' ? 'パスワード' : 'Password'); ?></label>
        <input id="m-pw" type="password" name="password" class="form-control"
               placeholder="<?php echo ui_attr($lang === 'ja' ? '8〜64文字、英数字・ハイフン・アンダーバー' : '8-64 letters, numbers, hyphen, or underscore'); ?>"
               minlength="8" maxlength="64" pattern="[a-zA-Z0-9_-]+" required autocomplete="new-password">
      </div>

      <button type="submit" class="btn btn-primary w-100"><?php echo ui_text($lang === 'ja' ? 'ユーザーを登録' : 'Register User'); ?></button>
    </form>
  </div>
</div>
<?php }; ?>
