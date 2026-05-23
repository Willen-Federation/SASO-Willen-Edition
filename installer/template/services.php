<?php
$this->title = '外部サービス連携';
$this->content = function ($v) {
    $currentStep = \saso\installer\WizardState::STEP_SERVICES;
    $stepTitle   = '外部サービス連携 (任意)';
    $stepLead    = 'Auth0 / Firebase の認証情報を登録できます。後から管理者コンソールで変更可能なので、未登録のまま次に進んでも構いません。';

    $flash = null;
    if (!empty($v->errorMessage)) {
        $flash = ['type' => 'error', 'message' => htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8')];
    }

    $stepBody = function () use ($v) {
        $h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
      <form method="post" action="./installer/services/" novalidate class="space-y-6">
        <section class="rounded-lg border bg-white p-4 dark:bg-boxdark" style="border-color:var(--saso-card-bdr,#e5e7eb)">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-3">Auth0 (Single Sign-On)</h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="auth0_domain">Domain</label>
              <input id="auth0_domain" name="auth0_domain"
                     value="<?php echo $h($v->auth0Domain); ?>"
                     placeholder="acme.eu.auth0.com"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="auth0_client_id">Client ID</label>
              <input id="auth0_client_id" name="auth0_client_id"
                     value="<?php echo $h($v->auth0ClientId); ?>"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1.5 block text-xs font-medium" for="auth0_client_secret">Client Secret</label>
              <input id="auth0_client_secret" name="auth0_client_secret" type="password"
                     placeholder="<?php echo $v->auth0ClientSecret !== '' ? '••••••••••••' : ''; ?>"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
              <p class="mt-1 text-xs text-gray-500">空欄の場合は変更しません。<code>APP_KEY</code> で暗号化された状態で保存されます。</p>
            </div>
          </div>
        </section>

        <section class="rounded-lg border bg-white p-4 dark:bg-boxdark" style="border-color:var(--saso-card-bdr,#e5e7eb)">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-3">Firebase</h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="firebase_project_id">Project ID</label>
              <input id="firebase_project_id" name="firebase_project_id"
                     value="<?php echo $h($v->firebaseProjectId); ?>"
                     placeholder="my-project-id"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="firebase_api_key">Web API Key</label>
              <input id="firebase_api_key" name="firebase_api_key" type="password"
                     placeholder="<?php echo $v->firebaseApiKey !== '' ? '••••••••••••' : 'AIza...'; ?>"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="firebase_auth_domain">Auth Domain</label>
              <input id="firebase_auth_domain" name="firebase_auth_domain"
                     value="<?php echo $h($v->firebaseAuthDomain); ?>"
                     placeholder="my-project.firebaseapp.com"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="firebase_storage_bucket">Storage Bucket</label>
              <input id="firebase_storage_bucket" name="firebase_storage_bucket"
                     value="<?php echo $h($v->firebaseStorage); ?>"
                     placeholder="my-project.appspot.com"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="firebase_messaging_sender_id">Messaging Sender ID</label>
              <input id="firebase_messaging_sender_id" name="firebase_messaging_sender_id"
                     value="<?php echo $h($v->firebaseSenderId); ?>"
                     placeholder="1234567890"
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium" for="firebase_app_id">App ID</label>
              <input id="firebase_app_id" name="firebase_app_id"
                     value="<?php echo $h($v->firebaseAppId); ?>"
                     placeholder="1:1234567890:web:abcdef..."
                     class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:bg-form-input dark:border-gray-700 dark:text-white">
            </div>
          </div>
        </section>

        <div class="flex items-center justify-between pt-2">
          <a href="./installer/security/" class="text-sm text-gray-500 hover:text-gray-700">戻る</a>
          <button type="submit"
                  class="inline-flex items-center justify-center rounded bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-opacity-90"
                  style="background:#3c50e0">
            保存して管理者作成へ
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
