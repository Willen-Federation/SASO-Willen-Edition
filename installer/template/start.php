<?php
$this->title = 'SASO セットアップ';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_WELCOME;
    $stepTitle   = 'ようこそ、SASO セットアップウィザードへ';
    $stepLead    = 'このウィザードでは、データベース接続情報・セキュリティキー・管理者アカウントなどを順番に登録します。各設定は <code>.env</code> ファイルと <code>system_setting</code> テーブルに保存されます。';

    $stepBody = function () use ($v) {
        $programDir = trim((string)($v->__get('programDir') ?? ''), '/');
        $base       = '/' . ($programDir !== '' ? $programDir . '/' : '');
        $nextUrl    = './installer/' . $v->nextStep . '/';
?>
      <div class="rounded-lg border bg-white p-4 dark:bg-boxdark"
           style="border-color:var(--saso-card-bdr,#e5e7eb)">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
          環境チェック
        </h3>
        <ul class="divide-y" style="border-color:var(--saso-card-bdr,#e5e7eb)">
          <?php foreach ($v->checks as $check): ?>
            <li class="flex items-start gap-3 py-2">
              <?php if ($check['ok']): ?>
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
              <?php else: ?>
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">!</span>
              <?php endif; ?>
              <span class="text-sm">
                <span class="font-medium block"><?php echo htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  <?php echo htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          進行状況に応じて、未完了のステップへ自動でジャンプします。
        </p>
        <a href="<?php echo htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8'); ?>"
           class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
           style="background:#3c50e0">
          次へ進む
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
