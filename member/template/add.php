<?php
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$this->title = $lang === 'ja' ? '新規ユーザー登録' : 'Register New User';
$this->content = function ($v) {
$lang = $_SESSION['lang'] ?? ($_COOKIE['saso_locale'] ?? 'ja');
$t = static fn (string $ja, string $en): string => $lang === 'ja' ? $ja : $en;
?>


<div class="mx-auto max-w-md">
  <div class="rounded-2xl border overflow-hidden"
       style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
    <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
      <h3 class="font-semibold" style="color:var(--saso-text)"><?php echo ui_text($t('ユーザー情報', 'User Details')); ?></h3>
    </div>
    <div class="px-5 py-5">
      <form action="./member/add/" method="POST" novalidate>
        <?php if (!empty($v->error)): ?>
          <div class="ta-alert ta-alert-danger mb-4" role="alert" aria-live="assertive">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
              <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <?php echo htmlspecialchars($v->error, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="mb-4">
          <label for="m-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
            <?php echo ui_text($t('ユーザーID', 'User ID')); ?>
            <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="m-id" type="text" name="id" class="form-input w-full"
                 placeholder="<?php echo ui_attr($t('8〜20文字の英数字ID', 'Enter alphanumeric User ID (8-20 chars)')); ?>"
                 required aria-required="true"
                 minlength="8" maxlength="20" pattern="[a-zA-Z0-9_-]+">
        </div>

        <div class="mb-4">
          <label for="m-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
            <?php echo ui_text($t('名前', 'Name')); ?>
            <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="m-name" type="text" name="userName" class="form-input w-full"
                 placeholder="<?php echo ui_attr($t('表示名を入力', 'Enter display name')); ?>"
                 required aria-required="true">
        </div>

        <div class="mb-5">
          <label for="m-pw" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">
            <?php echo ui_text($t('パスワード', 'Password')); ?>
            <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <input id="m-pw" type="password" name="password" class="form-input w-full"
                 placeholder="<?php echo ui_attr($t('8〜64文字、英数字・ハイフン・アンダーバー', '8-64 letters, numbers, hyphen, or underscore')); ?>"
                 minlength="8" maxlength="64" pattern="[a-zA-Z0-9_-]+"
                 required aria-required="true" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <?php echo ui_text($t('ユーザーを登録', 'Register User')); ?>
        </button>
      </form>
    </div>
  </div>
</div>
<?php }; ?>
