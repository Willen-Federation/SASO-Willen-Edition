<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? 'パスワード変更' : 'Change Password';
?>
<?php $this->content = function ($v) { ?>
<?php $lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja'); ?>

<div class="flex justify-center">
  <div class="w-full max-w-lg">
    <div class="rounded-2xl border shadow-sm" style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="px-6 py-5">

        <?php if ($v->changed) { ?>
          <div class="ta-alert ta-alert-success mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            パスワードが変更されました。
          </div>
        <?php } ?>
        <?php if ($v->errorNow) { ?>
          <div class="ta-alert ta-alert-danger mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            現在のパスワードが正しくありません。
          </div>
        <?php } ?>

        <p class="text-sm mb-5" style="color:var(--saso-text-sub)">パスワードはどこかに書き留めておいて下さい。忘れると、復元できません。</p>

        <form method="post" action="/start/password/">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-4">
            <label for="nowPassword" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">現在のパスワード</label>
            <input id="nowPassword" type="password" name="now" class="form-input w-full"
                   pattern="^[0-9a-zA-Z_-]{8,64}$" maxlength="64" required autocomplete="current-password"
                   aria-required="true">
          </div>
          <div class="mb-4">
            <label for="newPassword" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">新しいパスワード</label>
            <input id="newPassword" type="password" name="new" class="form-input w-full"
                   pattern="^[0-9a-zA-Z_-]{8,64}$" maxlength="64" required autocomplete="new-password"
                   aria-required="true">
            <p class="mt-1 text-xs" style="color:var(--saso-text-sub)"><?php echo ui_text($lang === 'ja' ? '半角英数字・ハイフン・アンダーバー、8〜64文字' : '8-64 letters, numbers, hyphen, or underscore'); ?></p>
          </div>
          <div class="mb-5">
            <label for="confirmPassword" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">新しいパスワード確認</label>
            <input id="confirmPassword" type="password" name="confirm" class="form-input w-full"
                   pattern="^[0-9a-zA-Z_-]{8,64}$" maxlength="64" required autocomplete="new-password"
                   aria-required="true">
            <p id="confirmPasswordError" class="mt-1 text-xs text-error-500 hidden" role="alert">パスワードが一致しません。</p>
          </div>
          <button id="changePasswordSubmit" type="submit" class="btn btn-primary" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            パスワード変更
          </button>
        </form>

      </div>
    </div>
  </div>
</div>

<?php } ?>
