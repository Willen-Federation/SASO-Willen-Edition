<?php $this->title = 'パスワード変更'; ?>
<?php $this->content = function ($v) { ?>

<ol class="breadcrumb mb-3" aria-label="breadcrumbs">
  <li class="breadcrumb-item"><a href="/">ホーム</a></li>
  <li class="breadcrumb-item active" aria-current="page">パスワード変更</li>
</ol>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body">

        <?php if ($v->changed) { ?>
          <div class="alert alert-success" role="alert">
            <i class="ti ti-circle-check me-2"></i>パスワードが変更されました。
          </div>
        <?php } ?>
        <?php if ($v->errorNow) { ?>
          <div class="alert alert-danger" role="alert">
            <i class="ti ti-alert-circle me-2"></i>現在のパスワードが正しくありません。
          </div>
        <?php } ?>

        <p class="text-secondary">パスワードはどこかに書き留めておいて下さい。忘れると、復元できません。</p>

        <form method="post" action="/start/password/">
          <div class="mb-3">
            <label for="nowPassword" class="form-label">現在のパスワード</label>
            <input id="nowPassword" type="password" name="now" class="form-control"
                   pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">新しいパスワード</label>
            <input id="newPassword" type="password" name="new" class="form-control"
                   pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required autocomplete="new-password">
            <div class="form-hint">半角英数、8〜20文字</div>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">新しいパスワード確認</label>
            <input id="confirmPassword" type="password" name="confirm" class="form-control"
                   pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required autocomplete="new-password">
            <div id="confirmPasswordError" class="invalid-feedback d-none">パスワードが一致しません。</div>
          </div>
          <div class="form-footer">
            <button id="changePasswordSubmit" type="submit" class="btn btn-primary" disabled>
              <i class="ti ti-key me-2"></i>パスワード変更
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<?php } ?>
