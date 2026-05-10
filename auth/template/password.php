<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? 'パスワード変更' : 'Change Password';
?>
<?php $this->content = function ($v) { ?>
<?php $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'); ?>

<div class="flex justify-center px-4 py-6">
  <div class="w-full max-w-lg">

    <?php if ($v->changed) { ?>
      <div class="ta-alert ta-alert-success mb-5" role="status">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm3.707-9.293a1 1 0 0 0-1.414-1.414L9 10.586 7.707 9.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span><?php echo ui_text($lang === 'ja' ? 'パスワードが変更されました。' : 'Password changed successfully.'); ?></span>
      </div>
    <?php } ?>

    <?php if ($v->errorNow) { ?>
      <div class="ta-alert ta-alert-danger mb-5" role="alert">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-.75-5.25a.75.75 0 0 0 1.5 0V10a.75.75 0 0 0-1.5 0v2.75zm0-5.5a.75.75 0 0 0 1.5 0v-.25a.75.75 0 0 0-1.5 0v.25z" clip-rule="evenodd"/>
        </svg>
        <span><?php echo ui_text($lang === 'ja' ? '現在のパスワードが正しくありません。' : 'The current password is incorrect.'); ?></span>
      </div>
    <?php } ?>

    <div class="rounded-2xl border"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr);box-shadow:0 2px 12px rgba(0,0,0,0.08)">
      <div class="border-b px-6 py-4" style="border-color:var(--saso-card-bdr)">
        <h2 class="text-base font-semibold" style="color:var(--saso-text)">
          <?php echo ui_text($lang === 'ja' ? 'パスワード変更' : 'Change Password'); ?>
        </h2>
        <p class="mt-1 text-sm" style="color:var(--saso-text-sub)">
          <?php echo ui_text($lang === 'ja'
            ? 'パスワードはどこかに書き留めておいて下さい。忘れると、復元できません。'
            : 'Write your password down somewhere safe — it cannot be recovered if forgotten.'); ?>
        </p>
      </div>

      <div class="px-6 py-6">
        <form method="post" action="/start/password/" novalidate>

          <div class="mb-4">
            <label for="nowPassword" class="form-label">
              <?php echo ui_text($lang === 'ja' ? '現在のパスワード' : 'Current Password'); ?>
            </label>
            <input id="nowPassword" type="password" name="now"
                   class="form-input"
                   pattern="^[0-9a-zA-Z_-]{8,64}$"
                   maxlength="64"
                   required
                   aria-required="true"
                   autocomplete="current-password">
          </div>

          <div class="mb-4">
            <label for="newPassword" class="form-label">
              <?php echo ui_text($lang === 'ja' ? '新しいパスワード' : 'New Password'); ?>
            </label>
            <input id="newPassword" type="password" name="new"
                   class="form-input"
                   pattern="^[0-9a-zA-Z_-]{8,64}$"
                   maxlength="64"
                   required
                   aria-required="true"
                   aria-describedby="newPasswordHint"
                   autocomplete="new-password">
            <p id="newPasswordHint" class="mt-1 text-xs" style="color:var(--saso-text-sub)">
              <?php echo ui_text($lang === 'ja' ? '半角英数字・ハイフン・アンダーバー、8〜64文字' : '8–64 characters: letters, numbers, hyphens, or underscores'); ?>
            </p>
          </div>

          <div class="mb-6">
            <label for="confirmPassword" class="form-label">
              <?php echo ui_text($lang === 'ja' ? '新しいパスワード確認' : 'Confirm New Password'); ?>
            </label>
            <input id="confirmPassword" type="password" name="confirm"
                   class="form-input"
                   pattern="^[0-9a-zA-Z_-]{8,64}$"
                   maxlength="64"
                   required
                   aria-required="true"
                   aria-describedby="confirmPasswordError"
                   autocomplete="new-password">
            <p id="confirmPasswordError" class="mt-1 hidden text-xs text-red-600 dark:text-red-400" role="alert">
              <?php echo ui_text($lang === 'ja' ? 'パスワードが一致しません。' : 'Passwords do not match.'); ?>
            </p>
          </div>

          <button id="changePasswordSubmit" type="submit"
                  class="btn btn-primary w-full"
                  disabled>
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 1-7.743 5.743L11 17H9v2H7v2H4a1 1 0 0 1-1-1v-2.586a1 1 0 0 1 .293-.707l5.964-5.964A6 6 0 1 1 21 9z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?php echo ui_text($lang === 'ja' ? 'パスワード変更' : 'Change Password'); ?></span>
          </button>
        </form>
      </div>
    </div>

  </div>
</div>
<?php }; ?>
