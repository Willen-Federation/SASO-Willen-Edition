<?php $this->title = 'パスワード変更'; ?>
<?php $this->content = function($v) {
  $lang = $_SESSION['lang'] ?? 'ja';
?>

<nav aria-label="<?php echo $lang === 'ja' ? 'パンくず' : 'breadcrumb'; ?>" class="mb-6">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $lang === 'ja' ? 'パスワード変更' : 'Change Password'; ?></li>
  </ol>
</nav>

<div class="mx-auto max-w-md">
  <div class="card">
    <div class="card-header">
      <h2 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'パスワードを変更' : 'Change Password'; ?></h2>
    </div>
    <div class="card-body">
      <?php if(!empty($v->changed)): ?>
      <div class="alert alert-success mb-4" role="status" aria-live="polite">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><?php echo $lang === 'ja' ? 'パスワードを変更しました' : 'Password changed successfully'; ?></span>
      </div>
      <?php endif; ?>
      <?php if(!empty($v->errorNow)): ?>
      <div class="alert alert-danger mb-4" role="alert" aria-live="polite">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><?php echo $lang === 'ja' ? '現在のパスワードが正しくありません' : 'Current password is incorrect'; ?></span>
      </div>
      <?php endif; ?>

      <div class="alert mb-4" style="border-color:#e2e8f0;background:#f8fafc;">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm text-body"><?php echo $lang === 'ja' ? 'パスワードはどこかに控えておいてください。忘れると復元できません。' : 'Keep your password somewhere safe. It cannot be recovered if forgotten.'; ?></span>
      </div>

      <form method="post" action="./start/password/" novalidate x-data="{
        pw: '', confirm: '',
        get match() { return this.pw && this.confirm && this.pw === this.confirm; }
      }">
        <div class="mb-4">
          <label for="nowPassword" class="form-label"><?php echo $lang === 'ja' ? '現在のパスワード' : 'Current Password'; ?> <span class="text-danger">*</span></label>
          <input id="nowPassword" type="password" name="now" pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required aria-required="true" class="form-input" autocomplete="current-password">
        </div>
        <div class="mb-4">
          <label for="newPassword" class="form-label"><?php echo $lang === 'ja' ? '新しいパスワード' : 'New Password'; ?> <span class="text-danger">*</span></label>
          <input id="newPassword" x-model="pw" type="password" name="new" pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required aria-required="true" class="form-input" autocomplete="new-password">
          <p class="mt-1 text-xs text-body"><?php echo $lang === 'ja' ? '半角英数、8〜20文字' : 'Alphanumeric, 8-20 characters'; ?></p>
        </div>
        <div class="mb-6">
          <label for="confirmPassword" class="form-label"><?php echo $lang === 'ja' ? '新しいパスワード（確認）' : 'Confirm New Password'; ?> <span class="text-danger">*</span></label>
          <input id="confirmPassword" x-model="confirm" type="password" name="confirm" pattern="^[0-9a-zA-Z]{8,20}$" maxlength="20" required aria-required="true" class="form-input" autocomplete="new-password">
          <p id="confirmPasswordError" x-show="confirm && !match" class="mt-1 text-xs text-danger" role="alert"><?php echo $lang === 'ja' ? 'パスワードが一致しません' : 'Passwords do not match'; ?></p>
        </div>
        <button id="changePasswordSubmit" type="submit" class="btn-primary w-full" :disabled="!match">
          <?php echo $lang === 'ja' ? 'パスワードを変更する' : 'Change Password'; ?>
        </button>
      </form>
    </div>
  </div>
</div>

<?php }; ?>