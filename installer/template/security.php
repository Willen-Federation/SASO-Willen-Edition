<?php
$this->title = 'セキュリティ設定';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_SECURITY;
    $stepTitle   = 'セキュリティキーと HTTPS 設定';
    $stepLead    = 'APP_KEY と JWT_SECRET は外部に漏れないように管理してください。値は自動生成しているのでそのままでも構いません。';

    $flash = null;
    if (!empty($v->errorMessage)) {
        $flash = ['type' => 'error', 'message' => htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8')];
    }

    $stepBody = function () use ($v) {
        $h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
      <form method="post" action="./installer/security/" novalidate class="space-y-4">
        <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700">
          各値は空欄のままで構いません。空欄の場合はインストーラが <code>base64_encode(random_bytes(32))</code> で安全な値を自動生成します。
          自前で生成済みの値があれば貼り付けてください (base64 32 バイト / hex 64 文字 / 32 文字以上の文字列のいずれか)。
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium" for="app_key">APP_KEY (任意 — 空欄で自動生成)</label>
          <input id="app_key" name="app_key"
                 value="<?php echo $h($v->appKey); ?>"
                 placeholder="空欄で自動生成 — base64-32B を生成します"
                 class="w-full rounded border border-gray-300 bg-white px-3 py-2 font-mono text-xs shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          <p class="mt-1 text-xs text-gray-500">AES-256-GCM のマスターキー。<code>openssl rand -base64 32</code> で生成した値を流用したい場合のみ入力してください。</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium" for="jwt_secret">JWT_SECRET (任意 — 空欄で自動生成)</label>
          <input id="jwt_secret" name="jwt_secret"
                 value="<?php echo $h($v->jwtSecret); ?>"
                 placeholder="空欄で自動生成 — base64-32B を生成します"
                 class="w-full rounded border border-gray-300 bg-white px-3 py-2 font-mono text-xs shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          <p class="mt-1 text-xs text-gray-500">モバイル / MCP 用 JWT の HMAC 秘密鍵。</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium" for="webhook_secret">WEBHOOK_SECRET (任意 — 空欄で自動生成)</label>
          <input id="webhook_secret" name="webhook_secret"
                 value="<?php echo $h($v->webhookSecret); ?>"
                 placeholder="空欄で自動生成 — base64-32B を生成します"
                 class="w-full rounded border border-gray-300 bg-white px-3 py-2 font-mono text-xs shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
          <p class="mt-1 text-xs text-gray-500"><code>POST /webhook</code> 用の <code>X-Webhook-Token</code> 値。</p>
        </div>

        <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-boxdark">
          <input type="checkbox" name="regenerate" value="1"
                 class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
          <span>
            <span class="text-sm font-medium block">既存の値があっても再生成する</span>
            <span class="text-xs text-gray-500">通常はオフのままにしてください。鍵ローテーションが必要な場合のみオンにします (既存暗号化データは復号できなくなります)。</span>
          </span>
        </label>

        <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-boxdark">
          <input type="checkbox" name="app_https" value="1" <?php echo $v->appHttps ? 'checked' : ''; ?>
                 class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
          <span>
            <span class="text-sm font-medium block">APP_HTTPS を有効にする</span>
            <span class="text-xs text-gray-500">HSTS ヘッダーとセッション Cookie の Secure フラグを付与します。リバースプロキシで TLS 終端している場合は必ず有効化してください。</span>
          </span>
        </label>

        <div class="flex items-center justify-between pt-2">
          <a href="./installer/database/" class="text-sm text-gray-500 hover:text-gray-700">戻る</a>
          <button type="submit"
                  class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
                  style="background:#3c50e0">
            保存して次へ
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
