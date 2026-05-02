<?php $this->content = function ($v) { ?>

<?php
  $lang   = $_SESSION['lang'] ?? 'ja';
  $isEdit = $v->mode === 'edit';

  // Parse claim_mapping
  $claimRaw = $v->provider['claim_mapping'] ?? '{}';
  $claimDecoded = is_string($claimRaw) ? json_decode($claimRaw, true) : (is_array($claimRaw) ? $claimRaw : []);
  if (!is_array($claimDecoded)) $claimDecoded = [];
  $cfg = is_array($claimDecoded['_config'] ?? null) ? $claimDecoded['_config'] : [];

  $claimOverrides = $claimDecoded;
  unset($claimOverrides['_config']);
  $claimOverridesJson = json_encode($claimOverrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($claimOverridesJson === '[]' || $claimOverridesJson === false) $claimOverridesJson = '{}';

  $provType = $v->provider['type'] ?? 'oidc';
  $flavor   = $v->flavor;

  // URLs for the reference box
  $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $baseUrl = $proto.'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');
  $loginUrl = $baseUrl.'/auth/login';

  if ($isEdit) {
      $displayCallback = $v->callbackUrl;
      $displayAcs      = $v->acsUrl;
      $displaySls      = $v->slsUrl;
      $urlsAreReal     = true;
  } else {
      $displayCallback = $baseUrl.'/auth/callback/...';
      $displayAcs      = $baseUrl.'/auth/saml/acs/...';
      $displaySls      = $baseUrl.'/auth/saml/sls/...';
      $urlsAreReal     = false;
  }

  // Firebase sub-providers
  $fbProviderOptions = [
    'google'    => 'Google',
    'apple'     => 'Apple',
    'facebook'  => 'Facebook',
    'github'    => 'GitHub',
    'twitter'   => 'Twitter / X',
    'microsoft' => 'Microsoft',
    'yahoo'     => 'Yahoo',
    'phone'     => $lang === 'ja' ? '電話番号' : 'Phone',
    'email'     => $lang === 'ja' ? 'メール / パスワード' : 'Email / Password',
  ];
  $fbProvidersEnabled = is_array($cfg['firebase_providers'] ?? null) ? $cfg['firebase_providers'] : [];
?>

<?php if (!$v->authorized): ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.auth_providers.forbidden_title', [], null, '管理者権限が必要です'),
    'body'    => __('ui.auth_providers.forbidden_body', [], null, '認証プロバイダを管理するには role=admin のユーザーでサインインしてください。'),
  ]); ?>
<?php else: ?>

<?php
ui('card', [
  'title'   => $isEdit
    ? ($lang === 'ja' ? '認証プロバイダ編集' : 'Edit Auth Provider')
    : ($lang === 'ja' ? '認証プロバイダ追加' : 'Add Auth Provider'),
  'actions' => function () use ($lang) {
      ui('button', [
          'label'   => $lang === 'ja' ? '一覧に戻る' : 'Back to list',
          'href'    => './auth/providers/',
          'type'    => 'link',
          'variant' => 'secondary',
      ]);
  },
  'body' => function () use (
      $v, $isEdit, $lang, $cfg, $claimOverridesJson, $provType, $flavor,
      $loginUrl, $displayCallback, $displayAcs, $displaySls, $urlsAreReal,
      $fbProviderOptions, $fbProvidersEnabled
  ) {
    $csrfToken = htmlspecialchars(\saso\util\CSRFtoken::current());
    // Initial Alpine state
    $initChoice = $isEdit ? $flavor : '';
    $initStep   = $isEdit ? 2 : 1;
?>

<form method="POST" action=""
      x-data="{
        choice: '<?php echo htmlspecialchars($initChoice, ENT_QUOTES); ?>',
        step:   <?php echo $initStep; ?>,
        verifyStatus: null,
        verifyMsg: '',
        verifyAuthUrl: null,
        get providerType() { return this.choice === 'saml' ? 'saml' : 'oidc'; },
        pick(c) { this.choice = c; this.step = 2; },
        async verify() {
          this.verifyStatus = 'loading'; this.verifyMsg = ''; this.verifyAuthUrl = null;
          const fd = new FormData();
          fd.append('csrftoken', document.querySelector('[name=csrftoken]').value);
          fd.append('type', this.providerType);
          fd.append('issuer_or_metadata_url',
                    document.querySelector('[name=issuer_or_metadata_url]')?.value ?? '');
          fd.append('client_id',   document.querySelector('[name=client_id]')?.value ?? '');
          fd.append('provider_id', '<?php echo (int) ($v->provider['id'] ?? 0); ?>');
          try {
            const r = await fetch('?action=verify', { method: 'POST', body: fd });
            const d = await r.json();
            this.verifyStatus  = d.ok ? 'ok' : 'error';
            this.verifyMsg     = d.ok ? d.detail : d.error;
            this.verifyAuthUrl = (d.ok && d.auth_url) ? d.auth_url : null;
          } catch(e) {
            this.verifyStatus = 'error';
            this.verifyMsg = e.message || '<?php echo $lang === 'ja' ? 'ネットワークエラー' : 'Network error'; ?>';
          }
        }
      }">

  <input type="hidden" name="csrftoken" value="<?php echo $csrfToken; ?>">
  <input type="hidden" name="type"   :value="providerType">
  <input type="hidden" name="flavor" :value="choice">

  <?php if (!empty($v->message)): ?>
    <?php ui('alert', ['variant' => $v->messageVariant ?? 'danger', 'body' => $v->message]); ?>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════
       STEP 1 — Provider Selection
  ═══════════════════════════════════════════════════════════ -->
  <div x-show="step === 1" x-cloak>

    <p class="mb-4 text-sm text-bodydark2">
      <?php echo $lang === 'ja'
        ? '設定する認証プロバイダを選択してください。'
        : 'Select the authentication provider you want to configure.'; ?>
    </p>

    <!-- Automatic providers -->
    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-bodydark2">
      <?php echo $lang === 'ja' ? '自動設定プロバイダ（推奨）' : 'Automatic Providers (recommended)'; ?>
    </p>
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
      <?php
      $autoCards = [
        'auth0'    => ['title' => 'Auth0',          'desc' => $lang === 'ja' ? 'Auth0 テナント・OIDC' : 'Auth0 tenant — OIDC'],
        'cognito'  => ['title' => 'AWS Cognito',     'desc' => $lang === 'ja' ? 'ユーザープール + Hosted UI' : 'User Pool + Hosted UI'],
        'firebase' => ['title' => 'Firebase Auth',   'desc' => $lang === 'ja' ? 'Google / Apple / Facebook 等' : 'Google / Apple / Facebook etc.'],
      ];
      foreach ($autoCards as $val => $info): ?>
        <button type="button"
                @click="pick('<?php echo $val; ?>')"
                class="flex flex-col gap-1 rounded-lg border-2 border-stroke p-4 text-left transition-colors hover:border-primary hover:bg-primary/5 dark:border-strokedark dark:hover:border-primary dark:hover:bg-primary/10">
          <span class="font-semibold text-black dark:text-white"><?php echo htmlspecialchars($info['title']); ?></span>
          <span class="text-xs text-bodydark2"><?php echo htmlspecialchars($info['desc']); ?></span>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Manual providers -->
    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-bodydark2">
      <?php echo $lang === 'ja' ? '手動設定プロバイダ' : 'Manual Providers'; ?>
    </p>
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
      <?php
      $manualCards = [
        'oidc' => ['title' => 'Generic OIDC', 'desc' => $lang === 'ja' ? '標準準拠の OIDC プロバイダ' : 'Any OIDC-compliant provider'],
        'saml' => ['title' => 'SAML 2.0',    'desc' => $lang === 'ja' ? 'Okta / ADFS 等のエンタープライズ IdP' : 'Okta, ADFS, or other enterprise IdP'],
      ];
      foreach ($manualCards as $val => $info): ?>
        <button type="button"
                @click="pick('<?php echo $val; ?>')"
                class="flex flex-col gap-1 rounded-lg border-2 border-stroke p-4 text-left transition-colors hover:border-primary hover:bg-primary/5 dark:border-strokedark dark:hover:border-primary dark:hover:bg-primary/10">
          <span class="font-semibold text-black dark:text-white"><?php echo htmlspecialchars($info['title']); ?></span>
          <span class="text-xs text-bodydark2"><?php echo htmlspecialchars($info['desc']); ?></span>
        </button>
      <?php endforeach; ?>
    </div>

  </div><!-- /step 1 -->

  <!-- ═══════════════════════════════════════════════════════
       STEP 2 — Configure
  ═══════════════════════════════════════════════════════════ -->
  <div x-show="step === 2" x-cloak>

    <?php if (!$isEdit): ?>

      <!-- ── NEW MODE: explain two-step flow, collect name only ── -->
      <button type="button"
              @click="step = 1; choice = ''"
              class="mb-5 inline-flex items-center gap-1 text-sm text-primary hover:underline">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M19 12H5m0 0 7 7m-7-7 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php echo $lang === 'ja' ? 'プロバイダを選び直す' : 'Choose a different provider'; ?>
      </button>

      <div class="mb-6 rounded-lg border border-primary/30 bg-primary/5 p-4 dark:border-primary/40 dark:bg-primary/10">
        <p class="mb-2 font-semibold text-black dark:text-white">
          <?php echo $lang === 'ja' ? '2ステップでプロバイダを追加します' : 'Two steps to add a provider'; ?>
        </p>
        <ol class="space-y-1 text-sm text-bodydark2">
          <li><span class="font-semibold text-primary">1.</span>
            <?php echo $lang === 'ja'
              ? '名前を入力して「プロバイダを作成」→ コールバック URL が発行されます'
              : 'Enter a name and click "Create provider" — your callback URL will be issued'; ?>
          </li>
          <li><span class="font-semibold text-primary">2.</span>
            <?php echo $lang === 'ja'
              ? 'コールバック URL を IdP に登録してから、クライアント情報を入力して保存'
              : 'Register the callback URL in your IdP, then enter client credentials and save'; ?>
          </li>
        </ol>
      </div>

      <!-- Provider Name -->
      <?php
      ui('formField', [
        'name'        => 'name',
        'label'       => $lang === 'ja' ? 'プロバイダ名' : 'Provider Name',
        'value'       => $v->provider['name'] ?? '',
        'required'    => true,
        'placeholder' => $lang === 'ja' ? 'プロバイダ名を入力' : 'Enter provider name',
      ]);
      ?>

      <!-- Create button -->
      <?php
      ui('button', [
        'label'      => $lang === 'ja' ? 'プロバイダを作成してコールバック URL を取得' : 'Create provider & get callback URL',
        'type'       => 'submit',
        'variant'    => 'primary',
        'extraClass' => 'w-full justify-center',
      ]);
      ?>

    <?php else: ?>

      <!-- ── EDIT MODE: real URLs + all credential fields ── -->

      <!-- URL reference box -->
      <div class="mb-6 overflow-hidden rounded-lg border border-stroke dark:border-strokedark">
        <div class="bg-gray-2 px-4 py-2.5 dark:bg-meta-4">
          <p class="text-xs font-semibold uppercase tracking-wider text-bodydark2">
            <?php echo $lang === 'ja' ? 'IdP に登録が必要な URL' : 'URLs to register with your IdP'; ?>
          </p>
        </div>
        <div class="divide-y divide-stroke px-4 dark:divide-strokedark">

          <?php
          $urlRow = function (string $label, string $value, string $note = '') use ($lang): void {
          ?>
            <div class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:gap-3">
              <span class="w-28 shrink-0 text-xs font-medium text-bodydark2"><?php echo htmlspecialchars($label); ?></span>
              <div class="flex grow items-center gap-2">
                <code class="grow truncate rounded bg-gray-2 px-2 py-1 font-mono text-xs text-black dark:bg-meta-4 dark:text-white">
                  <?php echo htmlspecialchars($value); ?>
                </code>
                <button type="button"
                        onclick="navigator.clipboard.writeText(<?php echo htmlspecialchars(json_encode($value)); ?>)"
                        title="<?php echo $lang === 'ja' ? 'コピー' : 'Copy'; ?>"
                        class="shrink-0 rounded border border-stroke p-1.5 text-bodydark2 transition hover:border-primary hover:text-primary dark:border-strokedark">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.5"/>
                  </svg>
                </button>
              </div>
              <?php if ($note !== ''): ?>
                <span class="shrink-0 text-xs text-bodydark2"><?php echo htmlspecialchars($note); ?></span>
              <?php endif; ?>
            </div>
          <?php
          };
          ?>

          <!-- Callback URL — OIDC only -->
          <div x-show="choice !== 'saml'">
            <?php $urlRow(
              'Callback URL',
              $displayCallback,
              $lang === 'ja' ? 'IdP の Allowed Callback URLs に登録' : 'Add to IdP Allowed Callback URLs'
            ); ?>
          </div>

          <!-- ACS + SLS — SAML only -->
          <div x-show="choice === 'saml'">
            <?php $urlRow('ACS URL', $displayAcs); ?>
          </div>
          <div x-show="choice === 'saml'">
            <?php $urlRow('SLS URL', $displaySls); ?>
          </div>

          <?php $urlRow(
            'Login URL',
            $loginUrl,
            $lang === 'ja' ? 'ユーザーがログインするページ' : 'Page where users sign in'
          ); ?>

        </div>
      </div>

      <!-- ── Provider Name (common) ── -->
      <?php
      ui('formField', [
        'name'        => 'name',
        'label'       => $lang === 'ja' ? 'プロバイダ名' : 'Provider Name',
        'value'       => $v->provider['name'] ?? '',
        'required'    => true,
        'placeholder' => $lang === 'ja' ? 'プロバイダ名を入力' : 'Enter provider name',
      ]);
      ?>

    <!-- ═══════════════════════════════════════════
         Auth0
    ════════════════════════════════════════════ -->
    <fieldset x-show="choice === 'auth0'" x-cloak :disabled="choice !== 'auth0'" class="m-0 min-w-0 border-0 p-0">
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-1 font-semibold text-black dark:text-white">Auth0</h4>
        <p class="text-xs text-bodydark2"><?php echo $lang === 'ja' ? 'Auth0 テナントの OIDC 設定' : 'Auth0 tenant OIDC configuration'; ?></p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => $lang === 'ja' ? 'Issuer / Discovery URL' : 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://acme.eu.auth0.com/',
        'help'        => $lang === 'ja' ? 'Auth0 テナントのルート URL。例: https://acme.eu.auth0.com/' : 'Auth0 tenant root URL, e.g. https://acme.eu.auth0.com/',
      ]);
      ui('formField', ['name' => 'client_id',  'label' => 'Client ID',     'value' => $v->provider['client_id'] ?? '', 'placeholder' => 'your-client-id']);
      ?>
      <div class="mb-4">
        <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
          Client Secret
          <?php if (!$v->hasSecret): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret
                   ? ($lang === 'ja' ? '●●●●●●●● （変更時のみ入力）' : '●●●●●●●● (enter only to change)')
                   : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
        <?php if ($v->hasSecret): ?>
          <p class="mt-1 text-xs text-bodydark2"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></p>
        <?php else: ?>
          <p class="mt-1 text-xs text-danger"><?php echo $lang === 'ja' ? '⚠ シークレット未設定。入力して保存するまでログインできません。' : '⚠ No client secret stored. Sign-in will fail until a secret is saved.'; ?></p>
        <?php endif; ?>
      </div>
      <?php
      ui('formField', [
        'name'        => 'auth0_domain',
        'label'       => $lang === 'ja' ? 'Auth0 ドメイン（オプション）' : 'Auth0 Domain (optional)',
        'value'       => $cfg['domain'] ?? '',
        'placeholder' => 'acme.eu.auth0.com',
        'help'        => $lang === 'ja'
            ? '空欄時は Issuer URL のホストを使います。ログアウト時の /v2/logout エンドポイント構築に使われます。'
            : 'If blank, the Issuer URL host is used. Drives the /v2/logout endpoint on sign-out.',
      ]);
      ui('formField', [
        'name'        => 'auth0_audience',
        'label'       => $lang === 'ja' ? 'Audience（オプション）' : 'Audience (optional)',
        'value'       => $cfg['audience'] ?? '',
        'placeholder' => 'https://api.example.com',
        'help'        => $lang === 'ja' ? 'Auth0 API の audience。不要な場合は空欄' : 'Auth0 API audience. Leave blank if not needed.',
      ]);
      ui('formField', [
        'name'        => 'scopes',
        'label'       => $lang === 'ja' ? 'スコープ' : 'Scopes',
        'value'       => $v->provider['scopes'] ?? '',
        'placeholder' => 'openid profile email',
        'help'        => $lang === 'ja' ? '空白区切り。空欄時は openid profile email' : 'Space-separated. Defaults to "openid profile email" if empty',
      ]);
      ?>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         AWS Cognito
    ════════════════════════════════════════════ -->
    <fieldset x-show="choice === 'cognito'" x-cloak :disabled="choice !== 'cognito'" class="m-0 min-w-0 border-0 p-0">
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-1 font-semibold text-black dark:text-white">AWS Cognito</h4>
        <p class="text-xs text-bodydark2"><?php echo $lang === 'ja' ? 'Cognito ユーザープール設定' : 'Cognito User Pool configuration'; ?></p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'cognito_region',
        'label'       => $lang === 'ja' ? 'リージョン' : 'Region',
        'value'       => $cfg['region'] ?? '',
        'placeholder' => 'ap-northeast-1',
        'help'        => $lang === 'ja' ? 'AWS リージョンコード' : 'AWS region code, e.g. ap-northeast-1',
      ]);
      ui('formField', [
        'name'        => 'cognito_user_pool_id',
        'label'       => $lang === 'ja' ? 'ユーザープール ID' : 'User Pool ID',
        'value'       => $cfg['user_pool_id'] ?? '',
        'placeholder' => 'ap-northeast-1_AbCdEfGhI',
        'help'        => $lang === 'ja' ? 'Cognito コンソールのユーザープール ID' : 'User Pool ID from the Cognito console',
      ]);
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => $lang === 'ja' ? 'Issuer / Discovery URL' : 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_xxx',
        'help'        => $lang === 'ja'
          ? '形式: https://cognito-idp.{region}.amazonaws.com/{pool_id}'
          : 'Format: https://cognito-idp.{region}.amazonaws.com/{pool_id}',
      ]);
      ui('formField', ['name' => 'client_id', 'label' => 'Client ID', 'value' => $v->provider['client_id'] ?? '', 'placeholder' => 'your-app-client-id']);
      ?>
      <div class="mb-4">
        <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
          Client Secret
          <?php if (!$v->hasSecret): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? '●●●●●●●●' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
        <?php if ($v->hasSecret): ?><p class="mt-1 text-xs text-bodydark2"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></p><?php else: ?><p class="mt-1 text-xs text-danger"><?php echo $lang === 'ja' ? '⚠ シークレット未設定。入力して保存するまでログインできません。' : '⚠ No client secret stored. Sign-in will fail until a secret is saved.'; ?></p><?php endif; ?>
      </div>
      <?php
      ui('formField', [
        'name'        => 'cognito_hosted_ui_domain',
        'label'       => $lang === 'ja' ? 'Hosted UI ドメイン' : 'Hosted UI Domain',
        'value'       => $cfg['hosted_ui_domain'] ?? '',
        'placeholder' => 'acme.auth.ap-northeast-1.amazoncognito.com',
        'help'        => $lang === 'ja' ? 'ログアウト URL 構築に使います' : 'Used to build the Cognito logout redirect URL',
      ]);
      ?>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         Firebase Auth
    ════════════════════════════════════════════ -->
    <fieldset x-show="choice === 'firebase'" x-cloak :disabled="choice !== 'firebase'" class="m-0 min-w-0 border-0 p-0">
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-1 font-semibold text-black dark:text-white">Firebase Auth</h4>
        <p class="text-xs text-bodydark2">
          <?php echo $lang === 'ja'
            ? 'Firebase Authentication は OIDC 経由で Google / Apple / Facebook 等のプロバイダをまとめて提供します。'
            : 'Firebase Authentication acts as an OIDC gateway for Google, Apple, Facebook, and other providers.'; ?>
        </p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'firebase_project_id',
        'label'       => $lang === 'ja' ? 'プロジェクト ID' : 'Project ID',
        'value'       => $cfg['project_id'] ?? '',
        'placeholder' => 'my-firebase-project',
        'help'        => $lang === 'ja' ? 'Firebase プロジェクト ID（参照用）' : 'Firebase project ID — for reference',
      ]);
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => $lang === 'ja' ? 'Issuer / Discovery URL' : 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://accounts.google.com',
        'help'        => $lang === 'ja'
          ? 'GCP OAuth 2.0 クライアントを使う場合は https://accounts.google.com を使用'
          : 'Use https://accounts.google.com for GCP OAuth 2.0 clients',
      ]);
      ui('formField', [
        'name'        => 'client_id',
        'label'       => 'Client ID',
        'value'       => $v->provider['client_id'] ?? '',
        'placeholder' => 'xxxxx.apps.googleusercontent.com',
        'help'        => $lang === 'ja' ? 'GCP コンソール → API とサービス → 認証情報 → OAuth 2.0 クライアント ID' : 'GCP Console → APIs & Services → Credentials → OAuth 2.0 Client ID',
      ]);
      ?>
      <div class="mb-4">
        <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
          Client Secret
          <?php if (!$v->hasSecret): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? '●●●●●●●●' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
        <?php if ($v->hasSecret): ?><p class="mt-1 text-xs text-bodydark2"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></p><?php else: ?><p class="mt-1 text-xs text-danger"><?php echo $lang === 'ja' ? '⚠ シークレット未設定。入力して保存するまでログインできません。' : '⚠ No client secret stored. Sign-in will fail until a secret is saved.'; ?></p><?php endif; ?>
      </div>
      <?php
      ui('formField', [
        'name'        => 'firebase_hd',
        'label'       => $lang === 'ja' ? 'ワークスペースドメイン hd（オプション）' : 'Workspace Domain hd (optional)',
        'value'       => $cfg['hd'] ?? '',
        'placeholder' => 'example.com',
        'help'        => $lang === 'ja' ? 'このドメイン以外のアカウントのログインを拒否します' : 'Logins from accounts outside this domain will be rejected',
      ]);
      ?>

      <!-- Firebase identity sub-providers -->
      <div class="mb-4">
        <p class="mb-2 block font-medium text-black dark:text-white">
          <?php echo $lang === 'ja' ? 'Firebase Auth で有効にした ID プロバイダ' : 'Identity Providers enabled in Firebase Auth'; ?>
        </p>
        <p class="mb-3 text-xs text-bodydark2">
          <?php echo $lang === 'ja'
            ? 'Firebase Authentication コンソールで有効にしているプロバイダにチェックを入れてください（参照用）。'
            : 'Check the providers you have enabled in your Firebase Authentication console (for reference).'; ?>
        </p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
          <?php foreach ($fbProviderOptions as $key => $label): ?>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-black dark:text-white">
              <input type="checkbox" name="firebase_providers[]" value="<?php echo htmlspecialchars($key); ?>"
                     <?php echo in_array($key, $fbProvidersEnabled, true) ? 'checked' : ''; ?>
                     class="h-4 w-4 rounded border-stroke accent-primary dark:border-strokedark">
              <?php echo htmlspecialchars($label); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         Generic OIDC
    ════════════════════════════════════════════ -->
    <fieldset x-show="choice === 'oidc'" x-cloak :disabled="choice !== 'oidc'" class="m-0 min-w-0 border-0 p-0">
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-1 font-semibold text-black dark:text-white">Generic OIDC</h4>
        <p class="text-xs text-bodydark2"><?php echo $lang === 'ja' ? '標準準拠の OpenID Connect プロバイダ' : 'Any standards-compliant OpenID Connect provider'; ?></p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => $lang === 'ja' ? 'Issuer / Discovery URL' : 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://example.com/.well-known/openid-configuration',
        'help'        => $lang === 'ja' ? 'OIDC プロバイダの Discovery URL' : 'OIDC provider discovery URL',
      ]);
      ui('formField', ['name' => 'client_id', 'label' => 'Client ID', 'value' => $v->provider['client_id'] ?? '', 'placeholder' => 'your-client-id']);
      ?>
      <div class="mb-4">
        <label for="client_secret" class="mb-2.5 block font-medium text-black dark:text-white">
          Client Secret
          <?php if (!$v->hasSecret): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? '●●●●●●●●' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white">
        <?php if ($v->hasSecret): ?><p class="mt-1 text-xs text-bodydark2"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></p><?php else: ?><p class="mt-1 text-xs text-danger"><?php echo $lang === 'ja' ? '⚠ シークレット未設定。入力して保存するまでログインできません。' : '⚠ No client secret stored. Sign-in will fail until a secret is saved.'; ?></p><?php endif; ?>
      </div>
      <?php
      ui('formField', [
        'name'        => 'scopes',
        'label'       => $lang === 'ja' ? 'スコープ' : 'Scopes',
        'value'       => $v->provider['scopes'] ?? '',
        'placeholder' => 'openid profile email',
        'help'        => $lang === 'ja' ? '空白区切り。空欄時は openid profile email' : 'Space-separated. Defaults to "openid profile email" if empty',
      ]);
      ?>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         SAML 2.0
    ════════════════════════════════════════════ -->
    <fieldset x-show="choice === 'saml'" x-cloak :disabled="choice !== 'saml'" class="m-0 min-w-0 border-0 p-0">
      <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
        <h4 class="mb-1 font-semibold text-black dark:text-white">SAML 2.0</h4>
        <p class="text-xs text-bodydark2"><?php echo $lang === 'ja' ? 'エンタープライズ IdP (Okta / ADFS 等)' : 'Enterprise IdP (Okta, ADFS, etc.)'; ?></p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => $lang === 'ja' ? 'Issuer / メタデータ URL' : 'Issuer / Metadata URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://idp.example.com/metadata',
        'help'        => $lang === 'ja' ? 'IdP の Entity ID または SSO URL / メタデータ URL' : 'IdP Entity ID, SSO URL, or metadata URL',
      ]);
      ui('formField', [
        'name'        => 'entity_id',
        'label'       => 'SP Entity ID',
        'value'       => $cfg['entity_id'] ?? '',
        'placeholder' => 'https://your-app.example.com/saml/metadata',
        'help'        => $lang === 'ja' ? '空の場合は ACS URL がデフォルト' : 'Defaults to the ACS URL if empty',
      ]);
      ui('formField', [
        'name'    => 'nameid_format',
        'label'   => 'NameID Format',
        'type'    => 'select',
        'value'   => $cfg['nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        'options' => [
          'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'   => 'Email Address',
          'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'     => 'Persistent',
          'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'      => 'Transient',
          'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'    => 'Unspecified',
        ],
      ]);
      ui('formField', [
        'name'        => 'idp_x509_cert',
        'label'       => $lang === 'ja' ? 'IdP 証明書 (X.509 PEM)' : 'IdP Certificate (X.509 PEM)',
        'type'        => 'textarea',
        'value'       => $cfg['idp_x509_cert'] ?? '',
        'rows'        => 6,
        'placeholder' => "-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----",
        'help'        => $lang === 'ja' ? 'IdP のメタデータから取得した X.509 証明書' : 'X.509 certificate from your IdP metadata',
      ]);
      ui('formField', [
        'name'        => 'sp_x509_cert',
        'label'       => $lang === 'ja' ? 'SP 証明書 (X.509 PEM, オプション)' : 'SP Certificate (X.509 PEM, optional)',
        'type'        => 'textarea',
        'value'       => $cfg['sp_x509_cert'] ?? '',
        'rows'        => 4,
        'placeholder' => "-----BEGIN CERTIFICATE-----\n...\n-----END CERTIFICATE-----",
        'help'        => $lang === 'ja' ? 'SP リクエスト署名用（任意）' : 'For SP request signing (optional)',
      ]);
      ui('formField', [
        'name'        => 'sp_private_key',
        'label'       => $lang === 'ja' ? 'SP 秘密鍵 (PEM, オプション)' : 'SP Private Key (PEM, optional)',
        'type'        => 'textarea',
        'value'       => $cfg['sp_private_key'] ?? '',
        'rows'        => 4,
        'placeholder' => "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----",
        'help'        => $lang === 'ja' ? 'SP 証明書に対応する秘密鍵（任意）' : 'Matching private key for the SP certificate (optional)',
      ]);
      ?>
    </fieldset>

    <!-- ── Advanced: claim mapping ── -->
    <div class="mb-4 border-t border-stroke pt-4 dark:border-strokedark">
      <details>
        <summary class="cursor-pointer text-xs font-semibold uppercase tracking-wider text-bodydark2">
          <?php echo $lang === 'ja' ? '詳細設定' : 'Advanced'; ?>
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
            'help'        => $lang === 'ja'
              ? 'IdP クレーム名のカスタムマッピング。_config は上の設定から自動生成されます'
              : 'Custom claim name overrides. _config is built automatically from the fields above',
          ]);
          ?>
        </div>
      </details>
    </div>

    <!-- ── Toggles ── -->
    <div class="mb-5 flex flex-wrap items-center gap-6">
      <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
        <?php
          // Default to checked when this is a brand-new shell row (no secret
          // stored yet) so the operator's first save-with-credentials flips
          // enabled=1 in one click. Otherwise mirror the DB state.
          $enabledDefault = !empty($v->provider['enabled']) || !$v->hasSecret;
        ?>
        <input type="checkbox" name="enabled" class="mr-1" <?php echo $enabledDefault ? 'checked' : ''; ?>>
        <?php echo $lang === 'ja' ? '有効' : 'Enabled'; ?>
      </label>
      <label class="flex cursor-pointer select-none items-center gap-2 text-black dark:text-white">
        <input type="checkbox" name="is_default" class="mr-1" <?php echo !empty($v->provider['is_default']) ? 'checked' : ''; ?>>
        <?php echo $lang === 'ja' ? 'デフォルトに設定' : 'Set as Default'; ?>
      </label>
    </div>

    <!-- ═══════════════════════════════════════════
         STEP 3 — Verify Connection
    ════════════════════════════════════════════ -->
    <div class="mb-5 border-t border-stroke pt-4 dark:border-strokedark">
      <p class="mb-3 text-sm font-medium text-black dark:text-white">
        <?php echo $lang === 'ja' ? '接続テスト' : 'Test Connection'; ?>
      </p>
      <p class="mb-3 text-xs text-bodydark2">
        <?php echo $lang === 'ja'
          ? 'Issuer URL（SAML の場合はメタデータ URL）が到達可能かどうかを確認します。'
          : 'Checks whether the Issuer URL (or SAML metadata URL) is reachable and returns a valid response.'; ?>
      </p>
      <button type="button"
              @click="verify()"
              :disabled="verifyStatus === 'loading'"
              class="inline-flex items-center gap-2 rounded border border-stroke bg-white px-4 py-2 text-sm font-medium text-black transition hover:border-primary hover:text-primary dark:border-strokedark dark:bg-boxdark dark:text-white dark:hover:border-primary dark:hover:text-primary disabled:opacity-50">
        <svg class="h-4 w-4" x-show="verifyStatus !== 'loading'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span x-show="verifyStatus !== 'loading'"><?php echo $lang === 'ja' ? '接続を確認する' : 'Verify Connection'; ?></span>
        <span x-show="verifyStatus === 'loading'" x-cloak><?php echo $lang === 'ja' ? '確認中...' : 'Checking…'; ?></span>
      </button>

      <div class="mt-3" x-show="verifyStatus !== null" x-cloak>
        <div x-show="verifyStatus === 'ok'" class="ta-alert ta-alert-success" role="status">
          <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="m4 12 5 5L20 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="grow" x-text="verifyMsg"></div>
        </div>
        <div x-show="verifyStatus === 'error'" class="ta-alert ta-alert-danger" role="alert">
          <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
            <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <div class="grow" x-text="verifyMsg"></div>
        </div>
        <template x-if="verifyStatus === 'ok' && verifyAuthUrl">
          <a :href="verifyAuthUrl" target="_blank" rel="noopener noreferrer"
             class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded border border-stroke bg-white px-4 py-2 text-sm font-medium text-black transition hover:border-primary hover:text-primary dark:border-strokedark dark:bg-boxdark dark:text-white dark:hover:border-primary dark:hover:text-primary">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6m0 0v6m0-6-9 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php echo ui_text($lang === 'ja' ? 'テストサインインを開く →' : 'Open Test Sign-In →'); ?>
          </a>
        </template>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════
         STEP 4 — Save
    ════════════════════════════════════════════ -->
    <?php
    ui('button', [
      'label'      => $lang === 'ja' ? '保存する' : 'Save',
      'type'       => 'submit',
      'variant'    => 'primary',
      'extraClass' => 'w-full justify-center',
    ]);
    ?>

    <?php endif; // end edit mode ?>

  </div><!-- /step 2 -->

</form>

<?php
  }
]);
?>

<?php endif; ?>

<?php }; ?>
