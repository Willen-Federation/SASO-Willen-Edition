<?php
$this->title = 'installer フォルダの削除';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_DONE;
    $stepTitle   = 'インストーラの自動削除に失敗しました';
    $stepLead    = '手動で <code>installer/</code> ディレクトリを削除してください。';

    $flash = ['type' => 'error', 'message' => htmlspecialchars($v->errorMessage ?? '不明なエラー', ENT_QUOTES, 'UTF-8')];

    $stepBody = function () {
?>
      <div class="rounded-lg border bg-white p-4 dark:bg-boxdark" style="border-color:var(--saso-card-bdr,#e5e7eb)">
        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">手動削除コマンド</h3>
        <pre class="rounded bg-black/90 p-3 text-xs text-green-300 overflow-x-auto"><code>rm -rf installer/</code></pre>
        <p class="mt-2 text-xs text-gray-500">FTP / cPanel など UI からも削除可能です。</p>
      </div>
      <div class="flex items-center justify-end pt-2">
        <a href="./installer/installed/" class="text-sm text-gray-500 hover:text-gray-700">完了画面に戻る</a>
      </div>
<?php
    };

    require __DIR__ . '/_wizard_shell.php';
};
?>
