<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? 'ログイン' : 'Login';
?>
<?php $this->content = function ($v) { ?>
<?php $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'); ?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card card-md">
      <div class="card-body">
        <h2 class="h2 text-center mb-4"><?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?></h2>

        <?php if ($v->isError) { ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'ID、パスワードが違います。' : 'The user ID or password is incorrect.'); ?>
          </div>
        <?php } ?>

        <form method="post" action="<?php echo htmlspecialchars($v->restoredPath, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="on">
          <div class="mb-3">
            <label for="login-id" class="form-label"><?php echo ui_text($lang === 'ja' ? 'ログインID' : 'Login ID'); ?></label>
            <input type="text" id="login-id" name="id" class="form-control" autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label for="login-password" class="form-label"><?php echo ui_text($lang === 'ja' ? 'パスワード' : 'Password'); ?></label>
            <input type="password" id="login-password" name="password" class="form-control" autocomplete="current-password" maxlength="64" required>
          </div>
          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'ログイン' : 'Login'); ?>
            </button>
          </div>
        </form>

        <div class="mt-3">
          <button type="button" class="btn btn-outline-primary w-100" id="passkey-login-btn">
            <i class="bi bi-fingerprint me-2" aria-hidden="true"></i><?php echo ui_text($lang === 'ja' ? 'パスキーでログイン' : 'Login with Passkey'); ?>
          </button>
        </div>

        <?php
          $externalProviders = array_values(array_filter(
              $v->providers,
              fn($p) => ($p->type->value ?? '') !== 'local'
          ));
        ?>
        <?php if ($externalProviders !== []) { ?>
          <div class="hr-text mt-4"><?php echo ui_text($lang === 'ja' ? '外部サービスでログイン' : 'Sign in with an external service'); ?></div>
          <div class="d-flex flex-column gap-2 mt-3">
            <?php foreach ($externalProviders as $p) { ?>
              <a href="/auth/start/<?php echo htmlspecialchars($p->id->value, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-key me-2" aria-hidden="true"></i>
                <?php echo ui_text($p->name . ($lang === 'ja' ? ' でログイン' : ' login')); ?>
                <span class="visually-hidden"> provider #<?php echo (int) $p->id->value; ?></span>
              </a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<script defer src="./js/passkey-login.js"></script>
<?php }; ?>
