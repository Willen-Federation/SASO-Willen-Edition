<?php
$this->title = 'インストール完了';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_DONE;
    $stepTitle   = 'インストールが完了しました 🎉';
    $stepLead    = '管理者アカウントが作成され、インストーラはロックされました。あとは <code>installer/</code> フォルダを削除すれば本番運用の準備は完了です。';
    $stepBody = function () use ($v) {
?>
      <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <strong>✅ 完了:</strong> 作成した管理者アカウントでログインできます。
      </div>

      <div class="rounded-lg border bg-white p-4 dark:bg-boxdark" style="border-color:var(--saso-card-bdr,#e5e7eb)">
        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">インストーラの削除</h3>
        <p class="text-xs text-gray-500 mb-3">セキュリティ上、<code>installer/</code> フォルダは削除することを推奨します。下のボタンで自動削除するか、FTP / シェルで手動削除してください。</p>
        <?php if ($v->installerStillPresent): ?>
        <form method="post" action="./installer/cleanup/"
              onsubmit="return confirm('installer/ フォルダを削除します。本当によろしいですか?');"
              class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <span class="text-xs text-gray-500"><code>installer/</code> は現在書き込み可能です。</span>
          <button type="submit"
                  class="inline-flex items-center justify-center rounded border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
            installer フォルダを削除する
          </button>
        </form>
        <?php else: ?>
          <p class="text-xs text-green-600">既に削除されています。</p>
        <?php endif; ?>
      </div>

      <div class="flex items-center justify-end pt-2">
        <a href="./auth/start/"
           class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
           style="background:#3c50e0">
          ログイン画面へ
          <svg class="ml-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
<?php
    };

    require __DIR__ . '/_wizard_shell.php';
};
?>
