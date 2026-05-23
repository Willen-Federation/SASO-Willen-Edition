<?php $this->title = 'ENV 設定'; ?>
<?php $this->content = function ($v) {
  $lang       = $_SESSION['lang'] ?? 'ja';
  $env        = $v->env ?? [];
  $settings   = $v->settings ?? [];
  $authorized = $v->authorized ?? false;
  $saved      = $v->saved ?? false;
  $loadError  = $v->loadError ?? null;
  $writeError = $v->writeError ?? null;
  $envPath    = $v->envPath ?? '';
  $writable   = $v->envWritable ?? false;

  $h = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  $mask = function (string $val): string {
    if ($val === '') return '';
    $tail = substr($val, -4);
    return str_repeat('•', max(4, strlen($val) - 4)) . $tail;
  };
?>
<?php if ($loadError !== null): ?>
<div class="mb-6 rounded-sm border border-error-500 bg-error-500 bg-opacity-10 px-4 py-3 text-error-500">
  <strong><?php echo $lang === 'ja' ? '設定の読み込み中にエラーが発生しました: ' : 'Error loading settings: '; ?></strong>
  <?php echo $h((string) $loadError); ?>
</div>
<?php endif; ?>

<?php if (!$authorized): ?>
<div class="rounded-sm border border-error-500 bg-error-500 bg-opacity-10 p-4 text-error-500">
  <?php echo $lang === 'ja' ? 'このページへのアクセス権限がありません。' : 'You do not have permission to access this page.'; ?>
</div>
<?php return; ?>
<?php endif; ?>

<?php if ($saved): ?>
<div class="mb-6 rounded-sm border border-success bg-success bg-opacity-10 px-4 py-3 text-success flex items-center gap-3">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
  <span><?php echo $lang === 'ja' ? '設定を保存しました。' : 'Settings saved successfully.'; ?></span>
</div>
<?php endif; ?>

<?php if ($writeError !== null): ?>
<div class="mb-6 rounded-sm border border-error-500 bg-error-500 bg-opacity-10 px-4 py-3 text-error-500">
  <strong><?php echo $lang === 'ja' ? '保存中にエラー: ' : 'Save error: '; ?></strong>
  <?php echo $h((string) $writeError); ?>
</div>
<?php endif; ?>

<?php if (!$writable): ?>
<div class="mb-6 rounded-sm border border-amber-300 bg-amber-50 px-4 py-3 text-amber-700">
  <strong><?php echo $lang === 'ja' ? '注意' : 'Notice'; ?>:</strong>
  <code><?php echo $h($envPath); ?></code>
  <?php echo $lang === 'ja' ? ' は書き込み不可です。.env への変更は反映されませんが、Auth0 / Firebase などのデータベース設定は更新できます。' : ' is not writable. Changes to .env will be skipped but database-backed settings can still be updated.'; ?>
</div>
<?php endif; ?>

<form method="post" action="" class="space-y-6">
  <input type="hidden" name="csrftoken" value="<?php echo $h(\saso\util\CSRFtoken::current()); ?>">

  <!-- Section 1: Database -->
  <section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'データベース接続' : 'Database Connection'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? 'PDO の DSN・ユーザー・パスワードを変更します。誤った値を保存するとログイン不能になるため注意してください。' : 'PDO DSN / user / password. Incorrect values will lock you out — proceed with care.'; ?></p>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium" for="db_dsn">DB_DSN</label>
        <input id="db_dsn" name="db_dsn" value="<?php echo $h($env['DB_DSN'] ?? ''); ?>"
               class="form-input w-full"
               placeholder="mysql:host=localhost;dbname=saso_db;charset=utf8mb4">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="db_user">DB_USER</label>
        <input id="db_user" name="db_user" value="<?php echo $h($env['DB_USER'] ?? ''); ?>" class="form-input w-full">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="db_password">DB_PASSWORD</label>
        <input id="db_password" name="db_password" type="password"
               placeholder="<?php echo ($env['DB_PASSWORD'] ?? '') !== '' ? '••••••' : ''; ?>"
               class="form-input w-full">
        <p class="mt-1 text-xs text-gray-500"><?php echo $lang === 'ja' ? '空欄の場合は現在の値を保持します。' : 'Leave blank to keep the current value.'; ?></p>
      </div>
    </div>
  </section>

  <!-- Section 2: Security toggles -->
  <section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'セキュリティ' : 'Security'; ?></h3>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <label class="flex items-start gap-3 sm:col-span-2 cursor-pointer">
        <input type="checkbox" name="app_https" value="1" <?php echo filter_var($env['APP_HTTPS'] ?? 'false', FILTER_VALIDATE_BOOLEAN) ? 'checked' : ''; ?>
               class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
        <span>
          <span class="font-medium text-sm block">APP_HTTPS</span>
          <span class="text-xs text-gray-500"><?php echo $lang === 'ja' ? 'HSTS と Cookie の Secure フラグを有効化します。' : 'Enable HSTS and the secure cookie flag.'; ?></span>
        </span>
      </label>

      <div>
        <label class="mb-1.5 block text-sm font-medium" for="app_key">APP_KEY</label>
        <input id="app_key" name="app_key" type="password"
               placeholder="<?php echo ($env['APP_KEY'] ?? '') !== '' ? $h($mask((string)($env['APP_KEY'] ?? ''))) : '32 bytes base64...'; ?>"
               class="form-input w-full font-mono text-xs">
        <p class="mt-1 text-xs text-gray-500"><?php echo $lang === 'ja' ? '空欄の場合は現在の値を保持します。' : 'Leave blank to keep the current value.'; ?></p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="jwt_secret">JWT_SECRET</label>
        <input id="jwt_secret" name="jwt_secret" type="password"
               placeholder="<?php echo ($env['JWT_SECRET'] ?? '') !== '' ? '••••••••' : 'hex 64 chars...'; ?>"
               class="form-input w-full font-mono text-xs">
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium" for="webhook_secret">WEBHOOK_SECRET</label>
        <input id="webhook_secret" name="webhook_secret" type="password"
               placeholder="<?php echo ($env['WEBHOOK_SECRET'] ?? '') !== '' ? '••••••••' : ''; ?>"
               class="form-input w-full font-mono text-xs">
      </div>
    </div>
  </section>

  <!-- Section 3: Document root overrides -->
  <section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'ドキュメントルート上書き' : 'Document Root Overrides'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? 'Docker / CI で使用するパス上書き。空欄推奨。' : 'Path overrides for Docker / CI deployments. Leave blank in most cases.'; ?></p>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="app_document_root">APP_DOCUMENT_ROOT</label>
        <input id="app_document_root" name="app_document_root"
               value="<?php echo $h($env['APP_DOCUMENT_ROOT'] ?? ''); ?>"
               class="form-input w-full"
               placeholder="/var/www/html/saso/">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="app_program_dir">APP_PROGRAM_DIR</label>
        <input id="app_program_dir" name="app_program_dir"
               value="<?php echo $h($env['APP_PROGRAM_DIR'] ?? ''); ?>"
               class="form-input w-full" placeholder="saso">
      </div>
    </div>
  </section>

  <!-- Section 4: Auth0 -->
  <section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'Auth0' : 'Auth0'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? 'Auth0 Management API および M2M 設定。' : 'Auth0 Management API and M2M credentials.'; ?></p>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="auth0_domain">Domain (system_setting)</label>
        <input id="auth0_domain" name="auth0_domain"
               value="<?php echo $h($settings['auth0.domain'] ?? ''); ?>"
               class="form-input w-full" placeholder="acme.eu.auth0.com">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="auth0_client_id">Client ID</label>
        <input id="auth0_client_id" name="auth0_client_id"
               value="<?php echo $h($settings['auth0.clientId'] ?? ''); ?>"
               class="form-input w-full">
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium" for="auth0_client_secret">Client Secret</label>
        <input id="auth0_client_secret" name="auth0_client_secret" type="password"
               placeholder="<?php echo ($settings['auth0.clientSecret'] ?? '') !== '' ? '••••••••' : ''; ?>"
               class="form-input w-full">
        <p class="mt-1 text-xs text-gray-500"><?php echo $lang === 'ja' ? 'APP_KEY で暗号化されて保存されます。' : 'Encrypted at rest using APP_KEY.'; ?></p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="auth0_m2m_domain">AUTH0_M2M_DOMAIN (.env)</label>
        <input id="auth0_m2m_domain" name="auth0_m2m_domain"
               value="<?php echo $h($env['AUTH0_M2M_DOMAIN'] ?? ''); ?>"
               class="form-input w-full">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="auth0_m2m_client_id">AUTH0_M2M_CLIENT_ID</label>
        <input id="auth0_m2m_client_id" name="auth0_m2m_client_id"
               value="<?php echo $h($env['AUTH0_M2M_CLIENT_ID'] ?? ''); ?>"
               class="form-input w-full">
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium" for="auth0_m2m_client_secret">AUTH0_M2M_CLIENT_SECRET</label>
        <input id="auth0_m2m_client_secret" name="auth0_m2m_client_secret" type="password"
               placeholder="<?php echo ($env['AUTH0_M2M_CLIENT_SECRET'] ?? '') !== '' ? '••••••••' : ''; ?>"
               class="form-input w-full">
      </div>
    </div>
  </section>

  <!-- Section 5: Firebase -->
  <section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white">Firebase</h3>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="firebase_project_id">Project ID</label>
        <input id="firebase_project_id" name="firebase_project_id"
               value="<?php echo $h($settings['firebase.project_id'] ?? ''); ?>"
               class="form-input w-full" placeholder="my-project-id">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="firebase_api_key">Web API Key</label>
        <input id="firebase_api_key" name="firebase_api_key" type="password"
               placeholder="<?php echo ($settings['firebase.api_key'] ?? '') !== '' ? '••••••••' : 'AIza...'; ?>"
               class="form-input w-full">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="firebase_auth_domain">Auth Domain</label>
        <input id="firebase_auth_domain" name="firebase_auth_domain"
               value="<?php echo $h($settings['firebase.auth_domain'] ?? ''); ?>"
               class="form-input w-full" placeholder="my-project.firebaseapp.com">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="firebase_storage_bucket">Storage Bucket</label>
        <input id="firebase_storage_bucket" name="firebase_storage_bucket"
               value="<?php echo $h($settings['firebase.storage_bucket'] ?? ''); ?>"
               class="form-input w-full">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="firebase_messaging_sender_id">Messaging Sender ID</label>
        <input id="firebase_messaging_sender_id" name="firebase_messaging_sender_id"
               value="<?php echo $h($settings['firebase.messaging_sender_id'] ?? ''); ?>"
               class="form-input w-full">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="firebase_app_id">App ID</label>
        <input id="firebase_app_id" name="firebase_app_id"
               value="<?php echo $h($settings['firebase.app_id'] ?? ''); ?>"
               class="form-input w-full">
      </div>
    </div>
  </section>

  <!-- Section 6: Bootstrap seed credentials -->
  <section class="rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <header class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'シード用 管理者' : 'Seed Admin Credentials'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? '"make seed" コマンドが利用するデフォルト管理者です。' : 'Default admin used by `make seed`.'; ?></p>
    </header>
    <div class="p-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="seed_admin_id">SEED_ADMIN_ID</label>
        <input id="seed_admin_id" name="seed_admin_id"
               value="<?php echo $h($env['SEED_ADMIN_ID'] ?? ''); ?>"
               class="form-input w-full">
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium" for="seed_admin_password">SEED_ADMIN_PASSWORD</label>
        <input id="seed_admin_password" name="seed_admin_password" type="password"
               placeholder="<?php echo ($env['SEED_ADMIN_PASSWORD'] ?? '') !== '' ? '••••••••' : ''; ?>"
               class="form-input w-full">
      </div>
    </div>
  </section>

  <div class="flex justify-end gap-3">
    <button type="submit" class="inline-flex items-center justify-center rounded bg-brand-500 px-8 py-3 font-medium text-white hover:bg-opacity-90 transition" style="background:#3c50e0">
      <?php echo $lang === 'ja' ? '設定を保存' : 'Save Settings'; ?>
    </button>
  </div>
</form>
<?php }; ?>
