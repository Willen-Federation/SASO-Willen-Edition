<?php
$this->title = 'スキーマ作成';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_SCHEMA;
    $stepTitle   = 'データベーススキーマの作成';
    $stepLead    = '接続したデータベースに SASO の必要なテーブルを作成します。既存テーブルはそのまま保持されます。';

    $flash = null;
    if (!empty($v->errorMessage)) {
        $flash = ['type' => 'error', 'message' => htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8')];
    } elseif ($v->alreadyInstalled) {
        $flash = ['type' => 'info', 'message' => '既に SASO のテーブルが存在しています。続行を選択するとスキーマは更新のみ行われます。'];
    }

    $stepBody = function () use ($v) {
        $h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
      <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:bg-boxdark dark:border-gray-700 dark:text-gray-200">
        次のステップでは次の処理を行います。
        <ul class="mt-2 ml-5 list-disc space-y-1 text-xs">
          <li>Member / Item / Category などの基本テーブルを <code>CREATE TABLE IF NOT EXISTS</code> で作成</li>
          <li>system_setting / system_setting_audit を作成し、認証・Firebase などの設定保存先を用意</li>
          <li>composer 依存関係がある場合は <code>vendor/bin/phinx migrate</code> を実行して M4 以降のスキーマを追加</li>
        </ul>
      </div>

      <?php if (!empty($v->log)): ?>
        <div class="rounded-md border border-gray-200 bg-black/90 p-4 font-mono text-xs text-green-300 overflow-x-auto">
          <?php foreach ($v->log as $line): ?>
            <div>$ <?php echo $h($line); ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="./installer/schema/" class="flex items-center justify-between pt-2">
        <a href="./installer/database/" class="text-sm text-gray-500 hover:text-gray-700">戻る</a>
        <button type="submit"
                class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
                style="background:#3c50e0">
          <?php echo $v->alreadyInstalled ? 'スキーマを更新して進む' : 'スキーマを作成する'; ?>
          <svg class="ml-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </form>
<?php
    };

    require __DIR__ . '/_wizard_shell.php';
};
?>
