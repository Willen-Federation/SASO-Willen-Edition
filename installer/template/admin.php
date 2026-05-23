<?php
$this->title = '管理者アカウント作成';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_ADMIN;
    $stepTitle   = '管理者アカウントの作成';
    $stepLead    = '最初の管理者ユーザーを登録します。ログイン ID とパスワードは大切に保管してください (復元できません)。';

    $flash = null;
    if (!empty($v->errorMessage)) {
        $flash = ['type' => 'error', 'message' => htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8')];
    }

    $stepBody = function () use ($v) {
        $h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
      <form method="post" action="./installer/admin/" novalidate class="space-y-4">
        <div>
          <label class="mb-1.5 block text-sm font-medium" for="name">お名前 <span class="text-rose-500">*</span></label>
          <input id="name" name="name" required maxlength="50"
                 value="<?php echo $h($v->name); ?>"
                 class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          <p class="mt-1 text-xs text-gray-500">50 字以内、日本語可。</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium" for="id">ログイン ID <span class="text-rose-500">*</span></label>
          <input id="id" name="id" required minlength="8" maxlength="20"
                 pattern="^[0-9a-zA-Z_\-]+$"
                 value="<?php echo $h($v->loginId); ?>"
                 autocomplete="username"
                 class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          <p class="mt-1 text-xs text-gray-500">8 〜 20 字、半角英数および「-」「_」。</p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="password">パスワード <span class="text-rose-500">*</span></label>
            <input id="password" name="password" type="password" required minlength="8" maxlength="20"
                   pattern="^[0-9a-zA-Z]+$"
                   autocomplete="new-password"
                   class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="password_confirm">パスワード (確認) <span class="text-rose-500">*</span></label>
            <input id="password_confirm" name="password_confirm" type="password" required minlength="8" maxlength="20"
                   pattern="^[0-9a-zA-Z]+$"
                   autocomplete="new-password"
                   class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          </div>
        </div>

        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
          <strong>注意:</strong> インストール完了後はインストーラがロックされます。ID とパスワードは安全な場所に控えてください。
        </div>

        <div class="flex items-center justify-between pt-2">
          <a href="./installer/services/" class="text-sm text-gray-500 hover:text-gray-700">戻る</a>
          <button type="submit"
                  class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
                  style="background:#10b981">
            インストールを完了する
            <svg class="ml-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </form>
<?php
    };

    require __DIR__ . '/_wizard_shell.php';
};
?>
