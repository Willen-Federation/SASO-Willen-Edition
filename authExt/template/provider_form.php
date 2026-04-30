<?php $this->content = function ($v) { ?>

<?php
  $lang    = $_SESSION['lang'] ?? 'ja';
  $isEdit  = $v->mode === 'edit';
  $flavor  = $v->flavor;      // 'auth0' | 'cognito' | 'saml' | 'oidc'
  $cfg     = $v->cfg;         // _config array from claim_mapping
  $base    = $v->baseUrl;

  // Heading per flavor
  $flavorLabels = [
    'auth0'   => 'Auth0',
    'cognito' => 'AWS Cognito',
    'saml'    => 'SAML 2.0',
    'oidc'    => 'Generic OIDC',
  ];
  $flavorLabel = $flavorLabels[$flavor] ?? strtoupper($flavor);

  $pageTitle = $isEdit
    ? ($lang === 'ja' ? "{$flavorLabel} プロバイダ編集" : "Edit {$flavorLabel} Provider")
    : ($lang === 'ja' ? "{$flavorLabel} プロバイダ追加" : "Add {$flavorLabel} Provider");

  // Callback / ACS URLs
  $callbackUrl = $v->callbackUrl;  // filled in edit mode
  $acsUrl      = $v->acsUrl;
  $slsUrl      = $v->slsUrl;
  // Pattern shown for new providers before ID is assigned
  $callbackPattern = $base . '/auth/callback/{id}';
  $acsPattern      = $base . '/auth/saml/acs/{id}';
  $slsPattern      = $base . '/auth/saml/sls/{id}';

  // Parsed claim-mapping overrides for textarea
  $claimRaw = $v->provider['claim_mapping'] ?? '{}';
  if (is_string($claimRaw)) {
      $claimDecoded = json_decode($claimRaw, true);
  } else {
      $claimDecoded = is_array($claimRaw) ? $claimRaw : [];
  }
  if (!is_array($claimDecoded)) $claimDecoded = [];
  $claimOverrides = $claimDecoded;
  unset($claimOverrides['_config']);
  $claimOverridesJson = json_encode($claimOverrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($claimOverridesJson === '[]' || $claimOverridesJson === false) $claimOverridesJson = '{}';
?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => $lang === 'ja' ? '管理者権限が必要です' : 'Admin access required',
    'body'    => $lang === 'ja' ? '認証プロバイダを管理するには role=admin のユーザーでサインインしてください。' : 'Sign in as a user with role=admin to manage authentication providers.',
  ]); ?>
<?php } else { ?>

<?php
  ui('card', [
    'title'   => $pageTitle,
    'actions' => function () use ($lang) {
        ui('button', [
            'label'   => $lang === 'ja' ? '一覧に戻る' : 'Back to list',
            'href'    => './auth/providers/',
            'type'    => 'link',
            'variant' => 'secondary',
        ]);
    },
    'body' => function () use ($v, $isEdit, $lang, $flavor, $flavorLabel, $cfg, $claimOverridesJson, $base, $callbackUrl, $callbackPattern, $acsUrl, $acsPattern, $slsUrl, $slsPattern) {
?>

  <?php if (!empty($v->message)): ?>
    <?php ui('alert', ['variant' => 'danger', 'body' => $v->message]); ?>
  <?php endif; ?>

  <!-- ══════════════════════════════════════════════════
       SETUP GUIDE
  ══════════════════════════════════════════════════ -->
  <div class="mb-6 rounded-xl border border-stroke bg-whiter p-5 dark:border-strokedark dark:bg-boxdark-2">
    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-bodydark2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <?php echo $lang === 'ja' ? "{$flavorLabel} セットアップガイド" : "{$flavorLabel} Setup Guide"; ?>
    </h3>

    <?php if ($flavor === 'auth0'): ?>
    <ol class="space-y-3 text-sm text-black dark:text-white">
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
        <span><?php echo $lang === 'ja'
          ? 'Auth0 ダッシュボード → <strong>Applications → Create Application</strong> → <em>Regular Web Application</em> を作成する'
          : 'Auth0 Dashboard → <strong>Applications → Create Application</strong> → choose <em>Regular Web Application</em>'; ?></span>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>Domain</strong>・<strong>Client ID</strong>・<strong>Client Secret</strong> をメモしてください（下のフォームに入力します）'
          : 'Note your <strong>Domain</strong>, <strong>Client ID</strong>, and <strong>Client Secret</strong> — enter them in the form below'; ?></span>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
        <div class="flex-1">
          <p><?php echo $lang === 'ja'
            ? 'アプリの <strong>Settings → Allowed Callback URLs</strong> に以下を追加してください：'
            : 'In the app\'s <strong>Settings → Allowed Callback URLs</strong>, add:'; ?></p>
          <?php echo renderUrlBox($isEdit ? $callbackUrl : $callbackPattern, $isEdit, $lang); ?>
        </div>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">4</span>
        <div class="flex-1">
          <p><?php echo $lang === 'ja'
            ? '<strong>Allowed Logout URLs</strong> に以下を追加してください：'
            : 'In <strong>Allowed Logout URLs</strong>, add:'; ?></p>
          <?php echo renderUrlBox($base, true, $lang); ?>
        </div>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">5</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>Allowed Web Origins</strong> に同じ URL を追加してください（CORS 対策）'
          : 'Add the same URL to <strong>Allowed Web Origins</strong> (CORS)'; ?></span>
      </li>
    </ol>
    <?php if (!$isEdit): ?>
    <p class="mt-4 rounded bg-warning bg-opacity-10 p-3 text-xs text-warning">
      <?php echo $lang === 'ja'
        ? '⚠ Callback URL の末尾 <code>{id}</code> は保存後に確定します。保存後に表示される実際の URL を Auth0 の設定に登録してください。'
        : '⚠ The <code>{id}</code> at the end of the Callback URL is assigned after saving. Register the actual URL shown after saving.'; ?>
    </p>
    <?php endif; ?>

    <?php elseif ($flavor === 'cognito'): ?>
    <ol class="space-y-3 text-sm text-black dark:text-white">
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
        <span><?php echo $lang === 'ja'
          ? 'AWS Console → Cognito → <strong>User Pools → Create user pool</strong>（または既存を選択）'
          : 'AWS Console → Cognito → <strong>User Pools → Create user pool</strong> (or select existing)'; ?></span>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>App clients</strong> でアプリを追加し、<em>Generate a client secret</em> を有効にしてください'
          : '<strong>App clients</strong> → add a client, enable <em>Generate a client secret</em>'; ?></span>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>App integration → Hosted UI</strong> の <em>Callback URL(s)</em> に以下を追加してください：'
          : 'In <strong>App integration → Hosted UI</strong>, add to <em>Callback URL(s)</em>:'; ?></span>
      </li>
      <?php echo '<li class="pl-9">' . renderUrlBox($isEdit ? $callbackUrl : $callbackPattern, $isEdit, $lang) . '</li>'; ?>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">4</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>Sign out URL(s)</strong> にアプリのベース URL を追加してください：'
          : 'Add your app base URL to <strong>Sign out URL(s)</strong>:'; ?></span>
      </li>
      <?php echo '<li class="pl-9">' . renderUrlBox($base, true, $lang) . '</li>'; ?>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">5</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>Region</strong>・<strong>User Pool ID</strong>・<strong>App client ID</strong>・<strong>Client secret</strong> をメモしてください'
          : 'Note your <strong>Region</strong>, <strong>User Pool ID</strong>, <strong>App client ID</strong>, and <strong>Client secret</strong>'; ?></span>
      </li>
    </ol>
    <?php if (!$isEdit): ?>
    <p class="mt-4 rounded bg-warning bg-opacity-10 p-3 text-xs text-warning">
      <?php echo $lang === 'ja'
        ? '⚠ Callback URL の末尾 <code>{id}</code> は保存後に確定します。保存後に表示される実際の URL を Cognito の設定に登録してください。'
        : '⚠ The <code>{id}</code> is assigned after saving. Register the actual URL shown after saving.'; ?>
    </p>
    <?php endif; ?>

    <?php elseif ($flavor === 'saml'): ?>
    <div class="text-sm text-black dark:text-white">
      <p class="mb-3 font-medium"><?php echo $lang === 'ja' ? '以下の SP 情報を IdP に登録してください：' : 'Register these SP details with your IdP:'; ?></p>
      <div class="space-y-3">
        <div>
          <p class="mb-1 text-xs font-semibold uppercase text-bodydark2"><?php echo $lang === 'ja' ? 'ACS URL（Assertion Consumer Service URL）' : 'ACS URL (Assertion Consumer Service URL)'; ?></p>
          <?php echo renderUrlBox($isEdit ? $acsUrl : $acsPattern, $isEdit, $lang); ?>
        </div>
        <div>
          <p class="mb-1 text-xs font-semibold uppercase text-bodydark2">SP Entity ID</p>
          <?php echo renderUrlBox($isEdit ? $acsUrl : $acsPattern, $isEdit, $lang); // SP Entity ID defaults to ACS URL ?>
        </div>
        <div>
          <p class="mb-1 text-xs font-semibold uppercase text-bodydark2"><?php echo $lang === 'ja' ? 'SLS URL（Single Logout Service URL）' : 'SLS URL (Single Logout Service URL)'; ?></p>
          <?php echo renderUrlBox($isEdit ? $slsUrl : $slsPattern, $isEdit, $lang); ?>
        </div>
      </div>
      <?php if (!$isEdit): ?>
      <p class="mt-4 rounded bg-warning bg-opacity-10 p-3 text-xs text-warning">
        <?php echo $lang === 'ja'
          ? '⚠ URL の末尾 <code>{id}</code> は保存後に確定します。保存後に表示される実際の URL を IdP の SP 設定に登録してください。'
          : '⚠ The <code>{id}</code> is assigned after saving. Register the actual URLs shown after saving in your IdP\'s SP configuration.'; ?>
      </p>
      <?php endif; ?>
      <p class="mt-3 text-xs text-bodydark2">
        <?php echo $lang === 'ja'
          ? '次に、IdP から <strong>メタデータ URL</strong> または <strong>Entity ID・SSO URL・X.509 証明書</strong> を取得して下のフォームに入力してください。'
          : 'Then, obtain the <strong>Metadata URL</strong> or <strong>Entity ID, SSO URL, and X.509 certificate</strong> from your IdP and enter them below.'; ?>
      </p>
    </div>

    <?php else: // Generic OIDC ?>
    <ol class="space-y-3 text-sm text-black dark:text-white">
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
        <span><?php echo $lang === 'ja'
          ? 'IdP のコンソールでアプリケーション（クライアント）を作成してください'
          : 'Create an application / client in your IdP\'s admin console'; ?></span>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
        <div class="flex-1">
          <p><?php echo $lang === 'ja'
            ? 'Redirect URI（コールバック URL）として以下を登録してください：'
            : 'Register the following as a Redirect URI (callback URL):'; ?></p>
          <?php echo renderUrlBox($isEdit ? $callbackUrl : $callbackPattern, $isEdit, $lang); ?>
        </div>
      </li>
      <li class="flex gap-3">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
        <span><?php echo $lang === 'ja'
          ? '<strong>Discovery URL</strong>（<code>/.well-known/openid-configuration</code>）・<strong>Client ID</strong>・<strong>Client Secret</strong> をメモしてください'
          : 'Note the <strong>Discovery URL</strong> (<code>/.well-known/openid-configuration</code>), <strong>Client ID</strong>, and <strong>Client Secret</strong>'; ?></span>
      </li>
    </ol>
    <?php if (!$isEdit): ?>
    <p class="mt-4 rounded bg-warning bg-opacity-10 p-3 text-xs text-warning">
      <?php echo $lang === 'ja'
        ? '⚠ Callback URL の末尾 <code>{id}</code> は保存後に確定します。保存後に実際の URL を IdP に登録してください。'
        : '⚠ The <code>{id}</code> is assigned after saving. Register the actual URL with your IdP after saving.'; ?>
    </p>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════════════════
       FORM
  ══════════════════════════════════════════════════ -->
  <form method="POST" action="" x-data="{
    auth0Domain: '<?php echo ui_attr($cfg['domain'] ?? ''); ?>',
    region: '<?php echo ui_attr($cfg['region'] ?? ''); ?>',
    poolId: '<?php echo ui_attr($cfg['user_pool_id'] ?? ''); ?>',
    syncAuth0Issuer() {
      if (!this.auth0Domain) return;
      var f = document.getElementById('issuer_or_metadata_url');
      if (f && f.value.trim() === '') f.value = 'https://' + this.auth0Domain + '/.well-known/openid-configuration';
    },
    syncCognitoIssuer() {
      if (!this.region || !this.poolId) return;
      var f = document.getElementById('issuer_or_metadata_url');
      if (f && f.value.trim() === '') f.value = 'https://cognito-idp.' + this.region + '.amazonaws.com/' + this.poolId + '/.well-known/openid-configuration';
    }
  }">
    <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current()); ?>">

    <!-- Provider Name (common) -->
    <?php
    ui('formField', [
      'name'        => 'name',
      'label'       => $lang === 'ja' ? 'プロバイダ名（ログイン画面のボタン表示名）' : 'Provider Name (shown on login button)',
      'value'       => $v->provider['name'] ?? '',
      'required'    => true,
      'placeholder' => $flavor === 'auth0' ? 'Auth0' : ($flavor === 'cognito' ? 'AWS Cognito' : ($flavor === 'saml' ? 'Okta SAML' : 'My OIDC Provider')),
    ]);
    ?>

    <!-- ── Auth0 fields ── -->
    <?php if ($flavor === 'auth0'): ?>

    <div class="mb-4">
      <label for="auth0_domain" class="mb-2.5 block font-medium text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'Auth0 ドメイン' : 'Auth0 Domain'; ?> <span class="text-meta-1">*</span>
      </label>
      <input type="text" id="auth0_domain" name="auth0_domain"
             x-model="auth0Domain" @input="syncAuth0Issuer()"
             value="<?php echo ui_attr($cfg['domain'] ?? ''); ?>"
             placeholder="your-tenant.auth0.com"
             class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
      <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'Auth0 ダッシュボードに表示されるドメイン（例: acme.auth0.com）' : 'The domain shown in your Auth0 dashboard (e.g. acme.auth0.com)'; ?></p>
    </div>

    <?php renderOidcCredentials($v, $lang); ?>

    <div class="mb-4">
      <label for="auth0_audience" class="mb-2.5 block font-medium text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'API Audience（任意）' : 'API Audience (optional)'; ?>
      </label>
      <input type="text" id="auth0_audience" name="auth0_audience"
             value="<?php echo ui_attr($cfg['audience'] ?? ''); ?>"
             placeholder="https://api.example.com"
             class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
      <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'API アクセストークンが必要な場合のみ設定（省略可）' : 'Only needed for API access tokens. Leave blank for web-only sign-in.'; ?></p>
    </div>

    <?php renderScopesField($v, $lang, 'openid profile email offline_access', $lang === 'ja' ? '空の場合: openid profile email offline_access（Auth0 推奨）' : 'Defaults to: openid profile email offline_access (Auth0 recommended)'); ?>

    <!-- hidden issuer auto-computed server-side; show as read-only reference -->
    <?php if ($isEdit && !empty($v->provider['issuer_or_metadata_url'])): ?>
    <div class="mb-4">
      <label class="mb-2.5 block font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? 'Issuer URL（自動）' : 'Issuer URL (auto)'; ?></label>
      <input type="text" readonly id="issuer_or_metadata_url" name="issuer_or_metadata_url"
             value="<?php echo ui_attr($v->provider['issuer_or_metadata_url'] ?? ''); ?>"
             class="w-full rounded border border-stroke bg-gray-2 py-3 px-5 font-mono text-sm text-black dark:border-form-strokedark dark:bg-meta-4 dark:text-white">
    </div>
    <?php else: ?>
    <input type="hidden" id="issuer_or_metadata_url" name="issuer_or_metadata_url" value="<?php echo ui_attr($v->provider['issuer_or_metadata_url'] ?? ''); ?>">
    <?php endif; ?>

    <!-- ── Cognito fields ── -->
    <?php elseif ($flavor === 'cognito'): ?>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div class="mb-4">
        <label for="region" class="mb-2.5 block font-medium text-black dark:text-white">
          AWS Region <span class="text-meta-1">*</span>
        </label>
        <input type="text" id="region" name="region"
               x-model="region" @input="syncCognitoIssuer()"
               value="<?php echo ui_attr($cfg['region'] ?? ''); ?>"
               placeholder="ap-northeast-1"
               class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
        <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'ユーザープールのリージョン（例: ap-northeast-1）' : 'The region of your User Pool (e.g. ap-northeast-1)'; ?></p>
      </div>
      <div class="mb-4">
        <label for="user_pool_id" class="mb-2.5 block font-medium text-black dark:text-white">
          User Pool ID <span class="text-meta-1">*</span>
        </label>
        <input type="text" id="user_pool_id" name="user_pool_id"
               x-model="poolId" @input="syncCognitoIssuer()"
               value="<?php echo ui_attr($cfg['user_pool_id'] ?? ''); ?>"
               placeholder="ap-northeast-1_AbCd12345"
               class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
        <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'Cognito コンソールのユーザープール ID' : 'User Pool ID from the Cognito console'; ?></p>
      </div>
    </div>

    <?php renderOidcCredentials($v, $lang); ?>

    <div class="mb-4">
      <label for="hosted_ui_domain" class="mb-2.5 block font-medium text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'Hosted UI ドメイン（任意）' : 'Hosted UI Domain (optional)'; ?>
      </label>
      <input type="text" id="hosted_ui_domain" name="hosted_ui_domain"
             value="<?php echo ui_attr($cfg['hosted_ui_domain'] ?? ''); ?>"
             placeholder="my-app.auth.ap-northeast-1.amazoncognito.com"
             class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
      <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'ログアウト URL の構築に使用。App integration → Domain で確認できます。' : 'Used to build the logout URL. Find it under App integration → Domain.'; ?></p>
    </div>

    <?php renderScopesField($v, $lang, 'openid profile email', $lang === 'ja' ? '空の場合: openid profile email' : 'Defaults to: openid profile email'); ?>

    <!-- Issuer (auto-computed or editable) -->
    <div class="mb-4">
      <label for="issuer_or_metadata_url" class="mb-2.5 block font-medium text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'Discovery URL（自動生成）' : 'Discovery URL (auto-built)'; ?>
      </label>
      <input type="text" id="issuer_or_metadata_url" name="issuer_or_metadata_url"
             value="<?php echo ui_attr($v->provider['issuer_or_metadata_url'] ?? ''); ?>"
             placeholder="https://cognito-idp.{region}.amazonaws.com/{pool-id}/.well-known/openid-configuration"
             class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-mono text-sm outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
      <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'Region と User Pool ID を入力すると自動補完されます。' : 'Auto-fills when Region and User Pool ID are entered. Override if needed.'; ?></p>
    </div>

    <!-- ── SAML fields ── -->
    <?php elseif ($flavor === 'saml'): ?>

    <?php
    ui('formField', [
      'name'        => 'issuer_or_metadata_url',
      'label'       => $lang === 'ja' ? 'IdP メタデータ URL または Entity ID' : 'IdP Metadata URL or Entity ID',
      'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
      'placeholder' => 'https://idp.example.com/saml/metadata',
      'help'        => $lang === 'ja' ? 'IdP のメタデータ URL を入力してください（推奨）。手動設定の場合は IdP Entity ID を入力します。' : 'Preferred: the IdP metadata URL. For manual setup: enter the IdP Entity ID.',
    ]);
    ?>

    <div class="mb-4">
      <label for="idp_x509_cert" class="mb-2.5 block font-medium text-black dark:text-white">
        <?php echo $lang === 'ja' ? 'IdP 証明書（X.509 PEM）' : 'IdP Certificate (X.509 PEM)'; ?> <span class="text-meta-1">*</span>
      </label>
      <textarea id="idp_x509_cert" name="idp_x509_cert" rows="6"
                placeholder="-----BEGIN CERTIFICATE-----&#10;MIID...&#10;-----END CERTIFICATE-----"
                class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-mono text-sm outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white"><?php echo ui_attr($cfg['idp_x509_cert'] ?? ''); ?></textarea>
      <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? 'IdP のメタデータから X.509 証明書を貼り付けてください。BEGIN/END ヘッダーを含めてください。' : 'Paste the IdP\'s X.509 signing certificate. Include the BEGIN/END headers.'; ?></p>
    </div>

    <?php
    ui('formField', [
      'name'    => 'nameid_format',
      'label'   => 'NameID Format',
      'type'    => 'select',
      'value'   => $cfg['nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
      'options' => [
        'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress' => 'Email Address',
        'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'   => 'Persistent',
        'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'    => 'Transient',
        'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'  => 'Unspecified',
      ],
    ]);
    ?>

    <details class="mb-4">
      <summary class="cursor-pointer text-sm font-medium text-primary"><?php echo $lang === 'ja' ? '高度な設定（SP 証明書・秘密鍵）' : 'Advanced (SP certificate & key)'; ?></summary>
      <div class="mt-3 space-y-4 pl-2">
        <div>
          <label for="sp_x509_cert" class="mb-2.5 block text-sm font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? 'SP 証明書（X.509 PEM）' : 'SP Certificate (X.509 PEM)'; ?></label>
          <textarea id="sp_x509_cert" name="sp_x509_cert" rows="4"
                    placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"
                    class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-mono text-sm outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white"><?php echo ui_attr($cfg['sp_x509_cert'] ?? ''); ?></textarea>
        </div>
        <div>
          <label for="sp_private_key" class="mb-2.5 block text-sm font-medium text-black dark:text-white"><?php echo $lang === 'ja' ? 'SP 秘密鍵（PEM）' : 'SP Private Key (PEM)'; ?></label>
          <textarea id="sp_private_key" name="sp_private_key" rows="4"
                    placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"
                    class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-mono text-sm outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white"><?php echo ui_attr($cfg['sp_private_key'] ?? ''); ?></textarea>
        </div>
        <?php
        ui('formField', [
          'name'        => 'entity_id',
          'label'       => 'SP Entity ID',
          'value'       => $cfg['entity_id'] ?? '',
          'placeholder' => $isEdit ? $acsUrl : $acsPattern,
          'help'        => $lang === 'ja' ? '省略した場合は ACS URL がデフォルトになります' : 'Defaults to the ACS URL if blank',
        ]);
        ?>
      </div>
    </details>

    <!-- ── Generic OIDC fields ── -->
    <?php else: ?>

    <?php
    ui('formField', [
      'name'        => 'issuer_or_metadata_url',
      'label'       => $lang === 'ja' ? 'Discovery URL (Issuer)' : 'Discovery URL (Issuer)',
      'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
      'placeholder' => 'https://accounts.example.com/.well-known/openid-configuration',
      'help'        => $lang === 'ja' ? 'OIDC プロバイダの Discovery URL（<code>/.well-known/openid-configuration</code> で終わる URL）' : 'The OIDC provider\'s discovery document URL (ending in /.well-known/openid-configuration)',
    ]);
    ?>

    <?php renderOidcCredentials($v, $lang); ?>
    <?php renderScopesField($v, $lang, 'openid profile email', $lang === 'ja' ? '空の場合: openid profile email' : 'Defaults to: openid profile email'); ?>

    <?php endif; ?>

    <!-- ── Callback URL display (edit mode only, after ID is known) ── -->
    <?php if ($isEdit): ?>
    <div class="mb-6 rounded-xl border-2 border-success border-opacity-50 bg-success bg-opacity-5 p-4">
      <h4 class="mb-3 text-sm font-semibold text-success"><?php echo $flavor === 'saml' ? ($lang === 'ja' ? 'IdP に登録する SP の URL' : 'SP URLs to register with your IdP') : ($lang === 'ja' ? 'IdP に登録するコールバック URL' : 'Callback URL to register with your IdP'); ?></h4>
      <?php if ($flavor === 'saml'): ?>
        <?php foreach (['ACS URL' => $acsUrl, 'SLS URL' => $slsUrl] as $label => $url): ?>
        <div class="mb-2">
          <p class="mb-1 text-xs text-bodydark2"><?php echo htmlspecialchars($label); ?></p>
          <?php echo renderUrlBox($url, true, $lang); ?>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?php echo renderUrlBox($callbackUrl, true, $lang); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Toggles ── -->
    <div class="mb-5.5 flex flex-wrap items-center gap-6">
      <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
        <input type="checkbox" name="enabled" class="mr-1" <?php echo !empty($v->provider['enabled']) ? 'checked' : ''; ?>>
        <?php echo $lang === 'ja' ? '有効' : 'Enabled'; ?>
      </label>
      <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
        <input type="checkbox" name="is_default" class="mr-1" <?php echo !empty($v->provider['is_default']) ? 'checked' : ''; ?>>
        <?php echo $lang === 'ja' ? 'デフォルトに設定（ログイン画面で最初に表示）' : 'Set as default (shown first on login screen)'; ?>
      </label>
    </div>

    <!-- ── Advanced: claim mapping overrides ── -->
    <details class="mb-6">
      <summary class="cursor-pointer text-sm font-medium text-bodydark2 hover:text-primary"><?php echo $lang === 'ja' ? '詳細設定（クレームマッピング）' : 'Advanced (claim mapping overrides)'; ?></summary>
      <div class="mt-3">
        <?php
        ui('formField', [
          'name'        => 'claim_mapping_raw',
          'label'       => $lang === 'ja' ? 'クレームマッピング (JSON)' : 'Claim Mapping Overrides (JSON)',
          'type'        => 'textarea',
          'value'       => $claimOverridesJson,
          'rows'        => 4,
          'placeholder' => '{"subject": "sub", "email": "email", "display_name": "name"}',
          'help'        => $lang === 'ja' ? 'IdP クレーム名が標準と異なる場合のみ設定してください' : 'Only needed when IdP claim names differ from the OIDC standard',
        ]);
        ?>
      </div>
    </details>

    <?php
    ui('button', [
      'label'      => $isEdit ? ($lang === 'ja' ? '更新する' : 'Update') : ($lang === 'ja' ? '保存する' : 'Save'),
      'type'       => 'submit',
      'variant'    => 'primary',
      'extraClass' => 'w-full justify-center',
    ]);
    ?>
  </form>

<?php
    },
  ]);
?>

<?php } ?>

<?php }; ?>

<?php
// ── Shared rendering helpers ──────────────────────────────────────────────────

function renderUrlBox(string $url, bool $withCopy, string $lang): string
{
    $esc = htmlspecialchars($url);
    $id  = 'url_' . substr(md5($url), 0, 6);
    $box = '<div class="flex items-center gap-2">'
         . '<input type="text" readonly id="'.$id.'" value="'.$esc.'"'
         . ' class="w-full rounded border border-stroke bg-gray-2 py-2 px-3 font-mono text-sm text-black dark:border-form-strokedark dark:bg-meta-4 dark:text-white"'
         . ' onclick="this.select()">'
         . '</div>';
    if ($withCopy) {
        $box = '<div class="flex items-center gap-2">'
             . '<input type="text" readonly id="'.$id.'" value="'.$esc.'"'
             . ' class="w-full rounded border border-stroke bg-gray-2 py-2 px-3 font-mono text-sm text-black dark:border-form-strokedark dark:bg-meta-4 dark:text-white"'
             . ' onclick="this.select()">'
             . '<button type="button" onclick="navigator.clipboard.writeText(\''.$esc.'\')" class="shrink-0 rounded border border-stroke bg-white px-3 py-2 text-xs font-medium text-bodydark2 hover:border-primary hover:text-primary dark:border-strokedark dark:bg-boxdark" title="Copy">📋</button>'
             . '</div>';
    }
    return $box;
}

function renderOidcCredentials(object $v, string $lang): void
{
    ui('formField', [
      'name'        => 'client_id',
      'label'       => 'Client ID',
      'value'       => $v->provider['client_id'] ?? '',
      'placeholder' => 'your-client-id',
    ]);
    ?>
    <div class="mb-4">
      <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
        Client Secret
      </label>
      <input type="password" id="client_secret" name="client_secret"
             value=""
             placeholder="<?php echo $v->hasSecret ? '●●●●●●●● ('.($lang==='ja'?'変更する場合のみ入力':'enter only to replace').')' : ($lang==='ja'?'シークレットを入力':'Enter client secret'); ?>"
             autocomplete="new-password"
             class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
      <?php if ($v->hasSecret): ?>
        <p class="mt-1 text-sm text-bodydark2"><?php echo $lang === 'ja' ? '既にシークレットが設定されています。変更する場合のみ入力してください。' : 'A secret is already set. Enter a new value only to replace it.'; ?></p>
      <?php endif; ?>
    </div>
    <?php
}

function renderScopesField(object $v, string $lang, string $default, string $help): void
{
    ui('formField', [
      'name'        => 'scopes',
      'label'       => $lang === 'ja' ? 'スコープ' : 'Scopes',
      'value'       => $v->provider['scopes'] ?? '',
      'placeholder' => $default,
      'help'        => $help,
    ]);
}
?>
