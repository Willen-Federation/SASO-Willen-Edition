<?php
$this->title = 'データベース設定';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_DATABASE;
    $stepTitle   = 'データベース接続情報';
    $stepLead    = 'SASO が使用する MySQL / MariaDB の接続情報を入力してください。入力内容は <code>.env</code> に保存されます。';

    $flash = null;
    if (!empty($v->errorMessage)) {
        $flash = ['type' => 'error', 'message' => htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8')];
    }

    $stepBody = function () use ($v) {
        $h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
      <form method="post" action="./installer/database/" novalidate class="space-y-4">
        <div>
          <label class="mb-1.5 block text-sm font-medium" for="dsn">PDO DSN <span class="text-rose-500">*</span></label>
          <input id="dsn" name="dsn" required
                 value="<?php echo $h($v->dsn); ?>"
                 placeholder="mysql:host=localhost;dbname=saso_db;charset=utf8mb4"
                 class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          <p class="mt-1 text-xs text-gray-500">例: <code>mysql:host=localhost;dbname=saso_db;charset=utf8mb4</code></p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="user">ユーザー名 <span class="text-rose-500">*</span></label>
            <input id="user" name="user" required
                   value="<?php echo $h($v->user); ?>"
                   placeholder="saso_user"
                   class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium" for="password">パスワード</label>
            <input id="password" name="password" type="password"
                   value="<?php echo $h($v->password); ?>"
                   autocomplete="new-password"
                   class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            <p class="mt-1 text-xs text-gray-500">空欄も許可しています(ローカル開発時など)。</p>
          </div>
        </div>

        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
          <strong>ヒント:</strong> 事前に空のデータベースを作成し、<code>CREATE / INSERT / SELECT / UPDATE / DELETE / ALTER</code> 権限を付与したユーザーを用意してください。
        </div>

        <div class="flex items-center justify-between pt-2">
          <a href="./installer/start/" class="text-sm text-gray-500 hover:text-gray-700">戻る</a>
          <button type="submit"
                  class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
                  style="background:#3c50e0">
            接続して保存
            <svg class="ml-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </form>
<?php
    };

    require __DIR__ . '/_wizard_shell.php';
};
?>
