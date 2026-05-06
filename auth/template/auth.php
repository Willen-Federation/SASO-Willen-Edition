<?php $this->title = 'ログイン'; ?>
<?php $this->content = function ($v) { ?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card card-md">
      <div class="card-body">
        <h2 class="h2 text-center mb-4">ログイン</h2>

        <?php if ($v->isError) { ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>ID、パスワードが違います。
          </div>
        <?php } ?>

        <form method="post" action="<?php echo htmlspecialchars($v->restoredPath, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="on">
          <div class="mb-3">
            <label for="login-id" class="form-label">ログインID</label>
            <input type="text" id="login-id" name="id" class="form-control" autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label for="login-password" class="form-label">パスワード</label>
            <input type="password" id="login-password" name="password" class="form-control" autocomplete="current-password" required>
          </div>
          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-box-arrow-in-right me-2"></i>ログイン
            </button>
          </div>
        </form>

        <?php if ($v->providers !== []) { ?>
          <div class="hr-text mt-4">外部サービスでログイン</div>
          <div class="d-flex flex-column gap-2 mt-3">
            <?php foreach ($v->providers as $p) { ?>
              <a href="/auth/start/<?php echo htmlspecialchars($p->id->value, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-key me-2"></i>
                <?php echo htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8'); ?> でログイン
              </a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php }; ?>
