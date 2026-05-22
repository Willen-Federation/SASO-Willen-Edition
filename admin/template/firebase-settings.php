<?php $this->title = 'Firebase設定'; ?>
<?php $this->content = function($v) {
  $lang              = $_SESSION['lang'] ?? 'ja';
  $settings          = $v->settings ?? [];
  $authorized        = $v->authorized ?? false;
  $saved             = $v->saved ?? false;
  $loadError         = $v->loadError ?? null;
  $apiKeyUnreadable  = $v->apiKeyUnreadable ?? false;

  // Current settings with defaults
  $apiKey         = $settings['firebase_api_key']             ?? '';
  $apiKeyExists   = $settings['firebase_api_key_exists']      ?? false;
  $authDomain     = $settings['firebase_auth_domain']         ?? '';
  $projectId      = $settings['firebase_project_id']          ?? '';
  $storage        = $settings['firebase_storage_bucket']      ?? '';
  $senderId       = $settings['firebase_messaging_sender_id'] ?? '';
  $appId          = $settings['firebase_app_id']              ?? '';

  // Masking helper: show only last 4 chars
  $maskKey = fn(string $key): string =>
    $key === '' ? '' : str_repeat('•', max(0, strlen($key) - 4)) . substr($key, -4);
?>

<?php if ($loadError !== null): ?>
<div class="mb-6 rounded-sm border border-error-500 bg-error-500 bg-opacity-10 px-4 py-3 text-error-500">
  <strong><?php echo $lang === 'ja' ? '設定の読み込み中にエラーが発生しました: ' : 'Error loading settings: '; ?></strong>
  <?php echo htmlspecialchars((string) $loadError, ENT_QUOTES, 'UTF-8'); ?>
  <?php if ($apiKeyUnreadable): ?>
    <p class="mt-2 text-sm">
      <?php echo $lang === 'ja'
        ? 'APP_KEY が変更されたため、保存済みの Firebase API キーを復号できません。下のフォームから新しい API キーを入力して保存してください。古い暗号文は上書きされます。'
        : 'The saved Firebase API key cannot be decrypted because APP_KEY has changed. Enter the new API key below and save — the stale ciphertext will be overwritten.'; ?>
    </p>
  <?php endif; ?>
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

<form method="post" action="">
  <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">

  <!-- ===== Section 1: Firebase Project ===== -->
  <div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? 'Firebaseプロジェクト情報' : 'Firebase Project Info'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? 'Firebaseコンソールから取得したプロジェクト設定を入力してください。' : 'Enter the project settings obtained from the Firebase console.'; ?></p>
    </div>
    <div class="p-6 space-y-4">
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label class="mb-2.5 block font-medium text-black dark:text-white" for="firebase_project_id">
            <?php echo $lang === 'ja' ? 'プロジェクトID' : 'Project ID'; ?>
          </label>
          <input type="text" id="firebase_project_id" name="firebase_project_id"
            value="<?php echo htmlspecialchars($projectId); ?>"
            class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="my-project-id">
        </div>
        <div>
          <label class="mb-2.5 block font-medium text-black dark:text-white" for="firebase_api_key">
            <?php echo $lang === 'ja' ? 'Web APIキー' : 'Web API Key'; ?>
          </label>
          <input type="password" id="firebase_api_key" name="firebase_api_key"
            placeholder="<?php echo $apiKeyExists ? '••••••••••••••••' : 'AIza...'; ?>"
            class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white">
          <?php if ($apiKeyUnreadable): ?>
            <p class="mt-1.5 text-xs text-error-500"><?php echo $lang === 'ja' ? '保存済みの値は現在の APP_KEY で復号できません。新しいキーを入力してください。' : 'The saved value cannot be decrypted with the current APP_KEY. Enter a new key.'; ?></p>
          <?php elseif ($apiKey !== ''): ?>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400"><?php echo $lang === 'ja' ? '設定済み: ' : 'Configured: '; ?><?php echo $maskKey($apiKey); ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label class="mb-2.5 block font-medium text-black dark:text-white" for="firebase_auth_domain">
            <?php echo $lang === 'ja' ? '認証ドメイン' : 'Auth Domain'; ?>
          </label>
          <input type="text" id="firebase_auth_domain" name="firebase_auth_domain"
            value="<?php echo htmlspecialchars($authDomain); ?>"
            class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="my-project.firebaseapp.com">
        </div>
        <div>
          <label class="mb-2.5 block font-medium text-black dark:text-white" for="firebase_app_id">
            <?php echo $lang === 'ja' ? 'アプリID' : 'App ID'; ?>
          </label>
          <input type="text" id="firebase_app_id" name="firebase_app_id"
            value="<?php echo htmlspecialchars($appId); ?>"
            class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="1:1234567890:web:abcdef...">
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label class="mb-2.5 block font-medium text-black dark:text-white" for="firebase_storage_bucket">
            <?php echo $lang === 'ja' ? 'ストレージバケット' : 'Storage Bucket'; ?>
          </label>
          <input type="text" id="firebase_storage_bucket" name="firebase_storage_bucket"
            value="<?php echo htmlspecialchars($storage); ?>"
            class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="my-project.appspot.com">
        </div>
        <div>
          <label class="mb-2.5 block font-medium text-black dark:text-white" for="firebase_messaging_sender_id">
            <?php echo $lang === 'ja' ? '送信者ID' : 'Messaging Sender ID'; ?>
          </label>
          <input type="text" id="firebase_messaging_sender_id" name="firebase_messaging_sender_id"
            value="<?php echo htmlspecialchars($senderId); ?>"
            class="w-full rounded border border-gray-200 bg-transparent py-3 px-4 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white"
            placeholder="1234567890">
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Section 2: Firebase Features (Feature Flags) ===== -->
  <div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default dark:border-gray-800 dark:bg-boxdark">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h3 class="font-semibold text-black dark:text-white"><?php echo $lang === 'ja' ? '機能有効化' : 'Enable Features'; ?></h3>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo $lang === 'ja' ? '各Firebase機能を有効にするかどうかを選択します。これらはフィーチャーフラグとしても管理されます。' : 'Choose whether to enable each Firebase feature. These are also managed as feature flags.'; ?></p>
    </div>
    <div class="p-6">
       <p class="text-sm text-brand-500 mb-4">
         <a href="./admin/feature-flags/" class="hover:underline flex items-center gap-1">
           <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
           <?php echo $lang === 'ja' ? '詳細なフィーチャーフラグ管理へ' : 'Go to detailed feature flag management'; ?>
         </a>
       </p>
       <div class="space-y-3">
         <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo $lang === 'ja' ? '※ ここで設定した内容は即座にシステム全体に反映されます。' : '* Changes made here take effect immediately across the entire system.'; ?></p>
       </div>
    </div>
  </div>

  <!-- ===== Save button ===== -->
  <div class="flex justify-end gap-3">
    <button type="submit"
      class="inline-flex items-center justify-center rounded bg-brand-500 px-8 py-3 font-medium text-white hover:bg-opacity-90 transition">
      <?php echo $lang === 'ja' ? '設定を保存' : 'Save Settings'; ?>
    </button>
  </div>

</form>

<?php }; ?>
