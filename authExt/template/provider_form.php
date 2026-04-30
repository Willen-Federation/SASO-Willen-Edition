<?php $this->content = function ($v) { ?>

<?php
  $lang   = $_SESSION['lang'] ?? 'ja';
  $isEdit = $v->mode === 'edit';
  $title  = $isEdit ? ($lang === 'ja' ? '認証プロバイダ編集' : 'Edit Auth Provider')
                     : ($lang === 'ja' ? '認証プロバイダ追加' : 'Add Auth Provider');

  // Parse claim_mapping for structured fields
  $claimRaw = $v->provider['claim_mapping'] ?? '{}';
  if (is_string($claimRaw)) {
      $claimDecoded = json_decode($claimRaw, true);
  } else {
      $claimDecoded = is_array($claimRaw) ? $claimRaw : [];
  }
  if (!is_array($claimDecoded)) $claimDecoded = [];
  $cfg = $claimDecoded['_config'] ?? [];
  if (!is_array($cfg)) $cfg = [];

  // Strip _config for the "raw overrides" textarea
  $claimOverrides = $claimDecoded;
  unset($claimOverrides['_config']);
  $claimOverridesJson = json_encode($claimOverrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($claimOverridesJson === '[]' || $claimOverridesJson === false) $claimOverridesJson = '{}';

  $provType      = $v->provider['type'] ?? 'oidc';
  $currentFlavor = $cfg['flavor'] ?? 'oidc';
  $providerId    = (int)($v->provider['id'] ?? 0);

  // Helper: safely escape a config value
  $cfgVal = fn(string $k): string => htmlspecialchars((string)($cfg[$k] ?? ''), ENT_QUOTES, 'UTF-8');
?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.auth_providers.forbidden_title', [], null, '管理者権限が必要です'),
    'body'    => __('ui.auth_providers.forbidden_body', [], null, '認証プロバイダを管理するには role=admin のユーザーでサインインしてください。'),
  ]); ?>
<?php } else { ?>

  <?php
    ui('card', [
      'title'   => $title,
      'actions' => function () use ($lang) {
          ui('button', [
              'label'   => $lang === 'ja' ? '一覧に戻る' : 'Back to list',
              'href'    => './auth/providers/',
              'type'    => 'link',
              'variant' => 'secondary',
          ]);
      },
      'body'    => function () use ($v, $isEdit, $lang, $cfg, $cfgVal, $claimOverridesJson, $provType, $currentFlavor, $providerId) {
  ?>
    <form method="POST" action=""
          x-data="{
            providerType: '<?php echo htmlspecialchars($provType, ENT_QUOTES); ?>',
            flavor: '<?php echo htmlspecialchars($currentFlavor, ENT_QUOTES); ?>',
            testStatus: '',
            testMessage: '',
            testRunning: false,
            async runTest() {
              this.testRunning = true;
              this.testStatus = '';
              this.testMessage = '';
              try {
                const res = await fetch('/api/v1/auth/providers/<?php echo $providerId; ?>/test', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (res.ok) {
                  this.testStatus = 'ok';
                  this.testMessage = data.message ?? '<?php echo $lang === 'ja' ? '接続テスト成功' : 'Connection test passed'; ?>';
                } else {
                  this.testStatus = 'error';
                  this.testMessage = data.detail ?? data.message ?? '<?php echo $lang === 'ja' ? 'テスト失敗' : 'Test failed'; ?>';
                }
              } catch(e) {
                this.testStatus = 'error';
                this.testMessage = e.message;
              } finally {
                this.testRunning = false;
              }
            }
          }">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current()); ?>">

      <?php if (!empty($v->message)): ?>
        <?php ui('alert', ['variant' => 'danger', 'body' => $v->message]); ?>
      <?php endif; ?>

      <!-- ────────────────────────────── Common Fields ────────────────────────────── -->
      <div class="mb-4 border-b border-stroke pb-4 dark:border-strokedark">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
          <?php echo $lang === 'ja' ? '基本設定' : 'Basic Settings'; ?>
        </h4>
      </div>

      <?php
      ui('formField', [
        'name'        => 'name',
        'label'       => $lang === 'ja' ? 'プロバイダ名' : 'Provider Name',
        'value'       => $v->provider['name'] ?? '',
        'required'    => true,
        'placeholder' => $lang === 'ja' ? 'プロバイダ名を入力（例: 社内Auth0）' : 'e.g. Corporate Auth0',
        'help'        => $lang === 'ja' ? 'ログイン画面に表示されるボタン名になります' : 'Shown on the login screen button',
      ]);
      ?>

      <!-- Provider Type Selector -->
      <div class="mb-4">
        <label class="mb-2.5 block font-medium text-black dark:text-white">
          <?php echo $lang === 'ja' ? 'プロバイダタイプ' : 'Provider Type'; ?>
          <span class="text-danger">*</span>
        </label>
        <div class="flex gap-4">
          <label class="flex cursor-pointer items-center gap-2 rounded border border-stroke px-4 py-3 transition hover:border-primary dark:border-strokedark"
                 :class="providerType === 'oidc' ? 'border-primary bg-primary/5 text-primary' : 'text-black dark:text-white'">
            <input type="radio" name="type" value="oidc" x-model="providerType" class="sr-only">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span class="font-medium">OIDC</span>
            <span class="text-xs text-bodydark2">(Auth0, Cognito, Firebase, Generic)</span>
          </label>
          <label class="flex cursor-pointer items-center gap-2 rounded border border-stroke px-4 py-3 transition hover:border-primary dark:border-strokedark"
                 :class="providerType === 'saml' ? 'border-primary bg-primary/5 text-primary' : 'text-black dark:text-white'">
            <input type="radio" name="type" value="saml" x-model="providerType" class="sr-only">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span class="font-medium">SAML 2.0</span>
          </label>
        </div>
      </div>

      <!-- ────────────────────────────── OIDC Settings ────────────────────────────── -->
      <div x-show="providerType === 'oidc'" x-cloak>
        <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
            <?php echo $lang === 'ja' ? 'OIDC プロバイダ選択' : 'OIDC Provider'; ?>
          </h4>
        </div>

        <!-- Flavor selection cards -->
        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <?php
          $flavors = [
            'oidc'     => ['label' => 'Generic OIDC', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'desc' => $lang === 'ja' ? 'カスタムOIDCプロバイダ' : 'Custom OIDC provider'],
            'auth0'    => ['label' => 'Auth0', 'icon' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z', 'desc' => $lang === 'ja' ? 'Auth0 テナント' : 'Auth0 tenant'],
            'cognito'  => ['label' => 'AWS Cognito', 'icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z', 'desc' => $lang === 'ja' ? 'AWSユーザープール' : 'AWS User Pool'],
            'firebase' => ['label' => 'Firebase', 'icon' => 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z', 'desc' => $lang === 'ja' ? 'Firebase / Google' : 'Firebase / Google'],
          ];
          foreach ($flavors as $flavorKey => $flavorData): ?>
          <label class="flex cursor-pointer flex-col items-center gap-2 rounded border border-stroke p-3 text-center transition hover:border-primary dark:border-strokedark"
                 :class="flavor === '<?php echo $flavorKey; ?>' ? 'border-primary bg-primary/5' : ''">
            <input type="radio" name="flavor" value="<?php echo $flavorKey; ?>" x-model="flavor" class="sr-only">
            <svg class="h-6 w-6" :class="flavor === '<?php echo $flavorKey; ?>' ? 'text-primary' : 'text-bodydark2'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $flavorData['icon']; ?>"/>
            </svg>
            <span class="text-sm font-medium" :class="flavor === '<?php echo $flavorKey; ?>' ? 'text-primary' : 'text-black dark:text-white'"><?php echo $flavorData['label']; ?></span>
            <span class="text-xs text-bodydark2"><?php echo $flavorData['desc']; ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Discovery / Issuer URL — shown for all OIDC flavors -->
        <div x-show="flavor !== 'cognito'">
          <?php
          ui('formField', [
            'name'        => 'issuer_or_metadata_url',
            'label'       => $lang === 'ja' ? 'Discovery URL (Issuer)' : 'Discovery URL (Issuer)',
            'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
            'placeholder' => 'https://your-domain/.well-known/openid-configuration',
            'help'        => $lang === 'ja' ? 'OIDCディスカバリーエンドポイントのURL。プロバイダのダッシュボードから確認できます。' : 'OIDC discovery endpoint. Found in your IdP dashboard.',
          ]);
          ?>
        </div>

        <!-- ── Auth0-specific fields ── -->
        <div x-show="flavor === 'auth0'" x-cloak>
          <div class="mb-4 mt-2 rounded-md border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-900/20">
            <p class="text-xs text-blue-700 dark:text-blue-300">
              <strong>Auth0</strong> — <?php echo $lang === 'ja' ? 'テナントドメインを入力してください（例: <code>your-tenant.us.auth0.com</code>）。Discovery URLは自動構築されます。' : 'Enter your Auth0 tenant domain (e.g. <code>your-tenant.us.auth0.com</code>). The Discovery URL is built automatically.'; ?>
            </p>
          </div>
          <?php
          ui('formField', [
            'name'        => 'auth0_domain',
            'label'       => $lang === 'ja' ? 'Auth0 ドメイン (Tenant)' : 'Auth0 Domain (Tenant)',
            'value'       => $cfgVal('domain'),
            'placeholder' => 'your-tenant.us.auth0.com',
            'help'        => $lang === 'ja' ? 'Auth0管理画面のSettings > Generalで確認できます' : 'Found in Auth0 Dashboard → Settings → General',
          ]);
          ui('formField', [
            'name'        => 'auth0_audience',
            'label'       => $lang === 'ja' ? 'Audience (オプション)' : 'Audience (optional)',
            'value'       => $cfgVal('audience'),
            'placeholder' => 'https://your-api.example.com',
            'help'        => $lang === 'ja' ? 'Auth0 APIのAudience。アクセストークンが必要な場合のみ設定してください。' : 'Auth0 API audience. Set only if you need access tokens for your API.',
          ]);
          ?>
        </div>

        <!-- ── AWS Cognito-specific fields ── -->
        <div x-show="flavor === 'cognito'" x-cloak>
          <div class="mb-4 mt-2 rounded-md border border-orange-200 bg-orange-50 p-3 dark:border-orange-900 dark:bg-orange-900/20">
            <p class="text-xs text-orange-700 dark:text-orange-300">
              <strong>AWS Cognito</strong> — <?php echo $lang === 'ja' ? 'リージョンとユーザープールIDを入力するとDiscovery URLが自動生成されます。' : 'Enter region and User Pool ID — the Discovery URL is generated automatically.'; ?>
            </p>
          </div>
          <?php
          ui('formField', [
            'name'        => 'cognito_region',
            'label'       => $lang === 'ja' ? 'AWSリージョン' : 'AWS Region',
            'value'       => $cfgVal('region'),
            'placeholder' => 'ap-northeast-1',
            'help'        => $lang === 'ja' ? '例: ap-northeast-1, us-east-1' : 'e.g. ap-northeast-1, us-east-1',
          ]);
          ui('formField', [
            'name'        => 'cognito_user_pool_id',
            'label'       => $lang === 'ja' ? 'ユーザープール ID' : 'User Pool ID',
            'value'       => $cfgVal('user_pool_id'),
            'placeholder' => 'ap-northeast-1_AbCd12345',
            'help'        => $lang === 'ja' ? 'AWS Cognito > ユーザープール > 概要 で確認できます' : 'AWS Console → Cognito → User Pools → Overview',
          ]);
          ui('formField', [
            'name'        => 'cognito_hosted_ui_domain',
            'label'       => $lang === 'ja' ? 'Hosted UI ドメイン' : 'Hosted UI Domain',
            'value'       => $cfgVal('hosted_ui_domain'),
            'placeholder' => 'your-pool.auth.ap-northeast-1.amazoncognito.com',
            'help'        => $lang === 'ja' ? 'ログアウト時に使用します。AWS Cognito > App integration > Domain で確認できます。' : 'Used for logout. Found in Cognito → App integration → Domain.',
          ]);
          ?>
          <!-- Auto-generated Discovery URL preview for Cognito -->
          <div class="mb-4 rounded border border-stroke bg-gray-2 p-3 dark:border-strokedark dark:bg-meta-4">
            <p class="mb-1 text-xs font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? 'Discovery URL (自動生成)' : 'Discovery URL (auto-generated)'; ?></p>
            <code class="text-xs text-bodydark2">
              https://cognito-idp.<span x-text="$el.closest('form').querySelector('[name=cognito_region]')?.value || '{region}'"></span>.amazonaws.com/<span x-text="$el.closest('form').querySelector('[name=cognito_user_pool_id]')?.value || '{user_pool_id}'"></span>/.well-known/openid-configuration
            </code>
          </div>
        </div>

        <!-- ── Firebase-specific fields ── -->
        <div x-show="flavor === 'firebase'" x-cloak>
          <div class="mb-4 mt-2 rounded-md border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-900 dark:bg-yellow-900/20">
            <p class="text-xs text-yellow-700 dark:text-yellow-300">
              <strong>Firebase Auth</strong> — <?php echo $lang === 'ja' ? 'Discovery URLには <code>https://accounts.google.com/.well-known/openid-configuration</code> を使用してください。Client IDとClient SecretはGoogle Cloud Consoleから取得してください（Firebaseプロジェクト設定からではありません）。' : 'Use <code>https://accounts.google.com/.well-known/openid-configuration</code> as the Discovery URL. Get Client ID and Secret from Google Cloud Console (not Firebase project settings).'; ?>
            </p>
          </div>
          <?php
          // Pre-fill discovery URL for firebase if empty
          ?>
          <div class="mb-4">
            <label class="mb-2.5 block font-medium text-black dark:text-white">Discovery URL</label>
            <input type="text" name="firebase_issuer_url"
                   value="<?php echo htmlspecialchars($v->provider['issuer_or_metadata_url'] ?? 'https://accounts.google.com/.well-known/openid-configuration', ENT_QUOTES); ?>"
                   placeholder="https://accounts.google.com/.well-known/openid-configuration"
                   class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
            <p class="mt-1 text-xs text-bodydark2"><?php echo $lang === 'ja' ? 'Google / Firebase の標準ディスカバリーエンドポイント' : 'Standard Google/Firebase discovery endpoint'; ?></p>
          </div>
          <?php
          ui('formField', [
            'name'        => 'firebase_project_id',
            'label'       => $lang === 'ja' ? 'Firebase プロジェクトID' : 'Firebase Project ID',
            'value'       => $cfgVal('project_id'),
            'placeholder' => 'my-firebase-project',
            'help'        => $lang === 'ja' ? 'ドキュメント用に記録します。Firebase Console > プロジェクトの設定 で確認できます。' : 'Recorded for documentation. Found in Firebase Console → Project settings.',
          ]);
          ui('formField', [
            'name'        => 'firebase_hd',
            'label'       => $lang === 'ja' ? 'Workspace ドメイン (hd) — オプション' : 'Workspace Domain (hd) — optional',
            'value'       => $cfgVal('hd'),
            'placeholder' => 'example.com',
            'help'        => $lang === 'ja' ? '設定するとこのGoogle Workspaceドメインのユーザーのみ許可されます。未設定なら全Googleアカウントが対象です。' : 'If set, only users from this Google Workspace domain are allowed.',
          ]);
          ?>
        </div>

        <!-- ── Client Credentials (all OIDC flavors) ── -->
        <div class="mb-4 mt-4 border-t border-stroke pt-4 dark:border-strokedark">
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
            <?php echo $lang === 'ja' ? 'クライアント認証情報' : 'Client Credentials'; ?>
          </h4>
        </div>

        <?php
        ui('formField', [
          'name'        => 'client_id',
          'label'       => 'Client ID',
          'value'       => $v->provider['client_id'] ?? '',
          'placeholder' => 'your-client-id',
          'help'        => $lang === 'ja' ? 'IdPのダッシュボードから取得したClient ID（アプリケーションID）' : 'Client ID (Application ID) from your IdP dashboard',
        ]);
        ?>

        <div class="mb-4">
          <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
            Client Secret
          </label>
          <input type="password" id="client_secret" name="client_secret"
                 value=""
                 placeholder="<?php echo $v->hasSecret ? '●●●●●●●● (leave blank to keep current)' : ($lang === 'ja' ? 'Client Secretを入力' : 'Enter client secret'); ?>"
                 autocomplete="new-password"
                 class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
          <?php if ($v->hasSecret): ?>
            <p class="mt-1 text-sm text-bodydark2">
              <?php echo $lang === 'ja' ? '既にシークレットが設定されています。変更する場合のみ入力してください。' : 'A secret is already set. Enter a value only to replace it.'; ?>
            </p>
          <?php endif; ?>
        </div>

        <?php
        ui('formField', [
          'name'        => 'scopes',
          'label'       => $lang === 'ja' ? 'スコープ' : 'Scopes',
          'value'       => $v->provider['scopes'] ?? '',
          'placeholder' => 'openid profile email',
          'help'        => $lang === 'ja' ? 'スペース区切り。空の場合は <code>openid profile email</code> がデフォルトです。' : 'Space-separated. Defaults to <code>openid profile email</code> if empty.',
        ]);
        ?>

        <!-- Callback URL (shown in edit mode, or as preview) -->
        <?php if ($isEdit && $v->callbackUrl !== ''): ?>
        <div class="mb-4">
          <label class="mb-2.5 block font-medium text-black dark:text-white">
            Callback URL (Redirect URI)
          </label>
          <div class="flex items-center gap-2">
            <input type="text" readonly
                   value="<?php echo htmlspecialchars($v->callbackUrl, ENT_QUOTES); ?>"
                   class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm outline-none dark:border-form-strokedark dark:bg-meta-4 text-black dark:text-white"
                   onclick="this.select()">
            <button type="button"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded border border-stroke transition hover:border-primary dark:border-strokedark"
                    onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($v->callbackUrl, ENT_QUOTES); ?>').then(() => this.textContent = '✓').catch(() => {})"
                    title="<?php echo $lang === 'ja' ? 'コピー' : 'Copy'; ?>">
              📋
            </button>
          </div>
          <p class="mt-1 text-xs text-bodydark2">
            <?php echo $lang === 'ja' ? 'IdPの Allowed Callback URLs / Redirect URIs に登録してください。' : 'Register this URL in your IdP\'s Allowed Callback URLs / Redirect URIs.'; ?>
          </p>
        </div>
        <?php else: ?>
        <div class="mb-4 rounded border border-stroke bg-gray-2 p-3 dark:border-strokedark dark:bg-meta-4">
          <p class="text-xs text-bodydark2">
            <strong><?php echo $lang === 'ja' ? 'Callback URL' : 'Callback URL'; ?></strong> —
            <?php echo $lang === 'ja' ? '保存後にCallback URLが表示されます。IdPに登録してください。' : 'The Callback URL will be shown after saving. Register it in your IdP.'; ?>
          </p>
        </div>
        <?php endif; ?>

      </div><!-- /oidc -->

      <!-- ────────────────────────────── SAML Settings ────────────────────────────── -->
      <div x-show="providerType === 'saml'" x-cloak>
        <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
            <?php echo $lang === 'ja' ? 'SAML 2.0 設定' : 'SAML 2.0 Settings'; ?>
          </h4>
          <div class="mb-4 rounded-md border border-purple-200 bg-purple-50 p-3 dark:border-purple-900 dark:bg-purple-900/20">
            <p class="text-xs text-purple-700 dark:text-purple-300">
              <?php echo $lang === 'ja' ? 'SAML 2.0 シングルサインオンの設定です。IdPのメタデータから情報を取得してください。' : 'SAML 2.0 single sign-on configuration. Obtain values from your IdP metadata.'; ?>
            </p>
          </div>
        </div>

        <?php
        ui('formField', [
          'name'        => 'issuer_or_metadata_url',
          'label'       => $lang === 'ja' ? 'IdP メタデータ URL または Entity ID' : 'IdP Metadata URL or Entity ID',
          'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
          'placeholder' => 'https://your-idp.example.com/saml/metadata',
          'help'        => $lang === 'ja' ? 'SAML IdPのメタデータURL（推奨）またはEntity ID' : 'SAML IdP metadata URL (recommended) or Entity ID',
        ]);
        ui('formField', [
          'name'        => 'entity_id',
          'label'       => 'SP Entity ID',
          'value'       => $cfg['entity_id'] ?? '',
          'placeholder' => 'https://your-app.example.com/saml/metadata',
          'help'        => $lang === 'ja' ? '空の場合はACS URLがデフォルトになります' : 'Defaults to the ACS URL if empty',
        ]);
        ui('formField', [
          'name'    => 'nameid_format',
          'label'   => 'NameID Format',
          'type'    => 'select',
          'value'   => $cfg['nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
          'options' => [
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'        => 'Email Address',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'          => 'Persistent',
            'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'           => 'Transient',
            'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'         => 'Unspecified',
          ],
        ]);
        ui('formField', [
          'name'        => 'idp_x509_cert',
          'label'       => $lang === 'ja' ? 'IdP 証明書 (X.509 PEM)' : 'IdP Certificate (X.509 PEM)',
          'type'        => 'textarea',
          'value'       => $cfg['idp_x509_cert'] ?? '',
          'rows'        => 6,
          'placeholder' => "-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----",
          'help'        => $lang === 'ja' ? 'IdPのメタデータからX.509証明書を貼り付けてください（BEGIN/END行含む）' : 'Paste the X.509 certificate from your IdP metadata (include BEGIN/END lines)',
        ]);
        ui('formField', [
          'name'        => 'sp_x509_cert',
          'label'       => $lang === 'ja' ? 'SP 証明書 (X.509 PEM) — オプション' : 'SP Certificate (X.509 PEM) — optional',
          'type'        => 'textarea',
          'value'       => $cfg['sp_x509_cert'] ?? '',
          'rows'        => 4,
          'placeholder' => "-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----",
          'help'        => $lang === 'ja' ? 'SPリクエスト署名用（オプション）' : 'For SP request signing (optional)',
        ]);
        ui('formField', [
          'name'        => 'sp_private_key',
          'label'       => $lang === 'ja' ? 'SP 秘密鍵 (PEM) — オプション' : 'SP Private Key (PEM) — optional',
          'type'        => 'textarea',
          'value'       => $cfg['sp_private_key'] ?? '',
          'rows'        => 4,
          'placeholder' => "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----",
          'help'        => $lang === 'ja' ? 'SP証明書に対応する秘密鍵（オプション）' : 'Matching private key for SP certificate (optional)',
        ]);
        ?>

        <?php if ($isEdit && $v->acsUrl !== ''): ?>
          <!-- ACS URL -->
          <div class="mb-4">
            <label class="mb-2.5 block font-medium text-black dark:text-white">
              ACS URL (Assertion Consumer Service)
            </label>
            <div class="flex items-center gap-2">
              <input type="text" readonly
                     value="<?php echo htmlspecialchars($v->acsUrl, ENT_QUOTES); ?>"
                     class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm outline-none dark:border-form-strokedark dark:bg-meta-4 text-black dark:text-white"
                     onclick="this.select()">
              <button type="button"
                      class="flex h-11 w-11 shrink-0 items-center justify-center rounded border border-stroke transition hover:border-primary dark:border-strokedark"
                      onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($v->acsUrl, ENT_QUOTES); ?>').then(() => this.textContent = '✓').catch(() => {})"
                      title="Copy">📋</button>
            </div>
            <p class="mt-1 text-xs text-bodydark2"><?php echo $lang === 'ja' ? 'IdPのSP設定に登録してください。' : 'Register in your IdP SP configuration.'; ?></p>
          </div>
          <!-- SLS URL -->
          <div class="mb-4">
            <label class="mb-2.5 block font-medium text-black dark:text-white">
              SLS URL (Single Logout Service)
            </label>
            <div class="flex items-center gap-2">
              <input type="text" readonly
                     value="<?php echo htmlspecialchars($v->slsUrl, ENT_QUOTES); ?>"
                     class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm outline-none dark:border-form-strokedark dark:bg-meta-4 text-black dark:text-white"
                     onclick="this.select()">
              <button type="button"
                      class="flex h-11 w-11 shrink-0 items-center justify-center rounded border border-stroke transition hover:border-primary dark:border-strokedark"
                      onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($v->slsUrl, ENT_QUOTES); ?>').then(() => this.textContent = '✓').catch(() => {})"
                      title="Copy">📋</button>
            </div>
          </div>
        <?php else: ?>
          <div class="mb-4 rounded border border-stroke bg-gray-2 p-3 dark:border-strokedark dark:bg-meta-4">
            <p class="text-xs text-bodydark2">
              <strong>ACS URL / SLS URL</strong> — <?php echo $lang === 'ja' ? '保存後に表示されます。IdPに登録してください。' : 'Shown after saving. Register in your IdP.'; ?>
            </p>
          </div>
        <?php endif; ?>
      </div><!-- /saml -->

      <!-- ────────────────────────────── Advanced: Claim Mapping ────────────────────────────── -->
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <details class="group">
          <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold uppercase tracking-wider text-bodydark2 hover:text-primary">
            <svg class="h-4 w-4 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <?php echo $lang === 'ja' ? '詳細設定 (クレームマッピング)' : 'Advanced (Claim Mapping)'; ?>
          </summary>
          <div class="mt-3">
            <?php
            ui('formField', [
              'name'        => 'claim_mapping_raw',
              'label'       => $lang === 'ja' ? 'クレームマッピング (JSON)' : 'Claim Mapping Overrides (JSON)',
              'type'        => 'textarea',
              'value'       => $claimOverridesJson,
              'rows'        => 4,
              'placeholder' => '{"subject": "sub", "email": "email", "display_name": "name"}',
              'help'        => $lang === 'ja' ? 'IdPクレーム名のカスタムマッピング。<code>_config</code>は上の設定から自動生成されます。' : 'Custom IdP claim name overrides. <code>_config</code> is built automatically from the fields above.',
            ]);
            ?>
          </div>
        </details>
      </div>

      <!-- ────────────────────────────── Toggles ────────────────────────────── -->
      <div class="mb-5 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-bodydark2">
          <?php echo $lang === 'ja' ? '公開設定' : 'Visibility'; ?>
        </h4>
        <div class="flex flex-wrap items-center gap-6">
          <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
            <input type="checkbox" name="enabled" class="mr-1" <?php echo !empty($v->provider['enabled']) ? 'checked' : ''; ?>>
            <?php echo $lang === 'ja' ? '有効（ログイン画面に表示）' : 'Enabled (shown on login screen)'; ?>
          </label>
          <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
            <input type="checkbox" name="is_default" class="mr-1" <?php echo !empty($v->provider['is_default']) ? 'checked' : ''; ?>>
            <?php echo $lang === 'ja' ? 'デフォルトプロバイダに設定' : 'Set as default provider'; ?>
          </label>
        </div>
      </div>

      <!-- ────────────────────────────── Actions ────────────────────────────── -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <?php
        ui('button', [
          'label'      => $isEdit ? ($lang === 'ja' ? '更新する' : 'Update') : ($lang === 'ja' ? '追加する' : 'Save Provider'),
          'type'       => 'submit',
          'variant'    => 'primary',
          'extraClass' => 'sm:flex-1 justify-center',
        ]);
        ?>

        <?php if ($isEdit && $providerId > 0): ?>
        <!-- Connection Test Button -->
        <button type="button"
                @click="runTest"
                :disabled="testRunning"
                class="flex items-center justify-center gap-2 rounded border border-stroke px-5 py-3 font-medium text-black transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-60 dark:border-strokedark dark:text-white">
          <svg x-show="!testRunning" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          <svg x-show="testRunning" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span x-text="testRunning ? '<?php echo $lang === 'ja' ? 'テスト中...' : 'Testing...'; ?>' : '<?php echo $lang === 'ja' ? '接続テスト' : 'Test Connection'; ?>'"></span>
        </button>
        <?php endif; ?>
      </div>

      <!-- Test Result -->
      <div x-show="testStatus !== ''" x-cloak class="mt-3">
        <div :class="testStatus === 'ok' ? 'bg-success/10 border-success text-success' : 'bg-danger/10 border-danger text-danger'"
             class="flex items-center gap-2 rounded border px-4 py-3 text-sm">
          <svg x-show="testStatus === 'ok'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <svg x-show="testStatus === 'error'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="testMessage"></span>
        </div>
      </div>

    </form>
  <?php
      },
    ]);
  ?>

<?php } ?>

<?php }; ?>
