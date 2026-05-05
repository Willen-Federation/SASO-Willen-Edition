<?php $this->content = function ($v) { ?>

<?php
  $lang   = $_SESSION['lang'] ?? 'ja';
  $isEdit = $v->mode === 'edit';

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

  $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $baseUrl = $proto.'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');
  $loginUrl = $baseUrl.'/auth/login';

  if ($isEdit) {
      $displayCallback = $v->callbackUrl;
      $displayAcs      = $v->acsUrl;
      $displaySls      = $v->slsUrl;
  } else {
      $displayCallback = $baseUrl.'/auth/callback';
      $displayAcs      = $baseUrl.'/auth/saml/acs';
      $displaySls      = $baseUrl.'/auth/saml/sls';
  }

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
      $loginUrl, $displayCallback, $displayAcs, $displaySls,
      $fbProviderOptions, $fbProvidersEnabled
  ) {
    $csrfToken = htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8');
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

    <p class="small text-muted mb-4">
      <?php echo $lang === 'ja'
        ? '設定する認証プロバイダを選択してください。'
        : 'Select the authentication provider you want to configure.'; ?>
    </p>

    <p class="text-uppercase small fw-semibold text-muted mb-2">
      <?php echo $lang === 'ja' ? '自動設定プロバイダ（推奨）' : 'Automatic Providers (recommended)'; ?>
    </p>
    <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
      <?php
      $autoCards = [
        'auth0'    => ['title' => 'Auth0',        'desc' => $lang === 'ja' ? 'Auth0 テナント・OIDC' : 'Auth0 tenant — OIDC',               'icon' => 'ti-shield-lock', 'tone' => 'primary'],
        'cognito'  => ['title' => 'AWS Cognito',   'desc' => $lang === 'ja' ? 'ユーザープール + Hosted UI' : 'User Pool + Hosted UI',       'icon' => 'ti-cloud',       'tone' => 'warning'],
        'firebase' => ['title' => 'Firebase Auth', 'desc' => $lang === 'ja' ? 'Google / Apple / Facebook 等' : 'Google / Apple / Facebook', 'icon' => 'ti-flame',       'tone' => 'danger'],
      ];
      foreach ($autoCards as $val => $info): ?>
        <div class="col">
          <button type="button"
                  @click="pick('<?php echo $val; ?>')"
                  class="card h-100 w-100 text-start border p-3"
                  role="option"
                  :aria-selected="choice === '<?php echo $val; ?>'">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="ti <?php echo htmlspecialchars($info['icon']); ?> text-<?php echo htmlspecialchars($info['tone']); ?>"></i>
              <span class="fw-semibold"><?php echo htmlspecialchars($info['title']); ?></span>
            </div>
            <span class="small text-muted"><?php echo htmlspecialchars($info['desc']); ?></span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="text-uppercase small fw-semibold text-muted mb-2">
      <?php echo $lang === 'ja' ? '手動設定プロバイダ' : 'Manual Providers'; ?>
    </p>
    <div class="row row-cols-1 row-cols-sm-2 g-3 mb-4">
      <?php
      $manualCards = [
        'oidc' => ['title' => 'Generic OIDC', 'desc' => $lang === 'ja' ? '標準準拠の OIDC プロバイダ' : 'Any OIDC-compliant provider',    'icon' => 'ti-key',       'tone' => 'secondary'],
        'saml' => ['title' => 'SAML 2.0',    'desc' => $lang === 'ja' ? 'Okta / ADFS 等のエンタープライズ IdP' : 'Okta, ADFS, enterprise', 'icon' => 'ti-building',  'tone' => 'secondary'],
      ];
      foreach ($manualCards as $val => $info): ?>
        <div class="col">
          <button type="button"
                  @click="pick('<?php echo $val; ?>')"
                  class="card h-100 w-100 text-start border p-3"
                  role="option"
                  :aria-selected="choice === '<?php echo $val; ?>'">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="ti <?php echo htmlspecialchars($info['icon']); ?> text-<?php echo htmlspecialchars($info['tone']); ?>"></i>
              <span class="fw-semibold"><?php echo htmlspecialchars($info['title']); ?></span>
            </div>
            <span class="small text-muted"><?php echo htmlspecialchars($info['desc']); ?></span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>

  </div><!-- /step 1 -->

  <!-- ═══════════════════════════════════════════════════════
       STEP 2 — Configure
  ═══════════════════════════════════════════════════════════ -->
  <div x-show="step === 2" x-cloak>

    <?php if (!$isEdit): ?>

      <button type="button"
              @click="step = 1; choice = ''"
              class="btn btn-link text-primary p-0 mb-4 d-inline-flex align-items-center gap-1 small text-decoration-none">
        <i class="ti ti-arrow-left" aria-hidden="true"></i>
        <?php echo $lang === 'ja' ? 'プロバイダを選び直す' : 'Choose a different provider'; ?>
      </button>

      <div class="alert alert-info mb-4">
        <p class="fw-semibold mb-2">
          <?php echo $lang === 'ja' ? '2ステップでプロバイダを追加します' : 'Two steps to add a provider'; ?>
        </p>
        <ol class="small mb-0 ps-3">
          <li class="mb-1">
            <span class="fw-semibold text-primary">1.</span>
            <?php echo $lang === 'ja'
              ? '名前を入力して「プロバイダを作成」→ コールバック URL が発行されます'
              : 'Enter a name and click "Create provider" — your callback URL will be issued'; ?>
          </li>
          <li>
            <span class="fw-semibold text-primary">2.</span>
            <?php echo $lang === 'ja'
              ? 'コールバック URL を IdP に登録してから、クライアント情報を入力して保存'
              : 'Register the callback URL in your IdP, then enter client credentials and save'; ?>
          </li>
        </ol>
      </div>

      <?php
      ui('formField', [
        'name'        => 'name',
        'label'       => $lang === 'ja' ? 'プロバイダ名' : 'Provider Name',
        'value'       => $v->provider['name'] ?? '',
        'required'    => true,
        'placeholder' => $lang === 'ja' ? 'プロバイダ名を入力' : 'Enter provider name',
      ]);
      ui('button', [
        'label'      => $lang === 'ja' ? 'プロバイダを作成してコールバック URL を取得' : 'Create provider & get callback URL',
        'type'       => 'submit',
        'variant'    => 'primary',
        'extraClass' => 'w-100',
      ]);
      ?>

    <?php else: ?>

      <!-- URL reference box -->
      <div class="border rounded mb-4 overflow-hidden">
        <div class="bg-light px-3 py-2 border-bottom">
          <p class="text-uppercase small fw-semibold text-muted mb-0">
            <?php echo $lang === 'ja' ? 'IdP に登録が必要な URL' : 'URLs to register with your IdP'; ?>
          </p>
        </div>
        <div class="px-3">
          <?php
          $urlRow = function (string $label, string $value, string $note = '') use ($lang): void {
          ?>
            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 py-2 border-top">
              <span class="small fw-medium text-muted" style="min-width:7rem;"><?php echo htmlspecialchars($label); ?></span>
              <div class="d-flex align-items-center gap-2 flex-fill">
                <code class="form-control form-control-sm font-monospace flex-fill bg-light text-body" style="overflow-x:auto;">
                  <?php echo htmlspecialchars($value); ?>
                </code>
                <button type="button"
                        onclick="navigator.clipboard.writeText(<?php echo htmlspecialchars(json_encode($value)); ?>)"
                        title="<?php echo $lang === 'ja' ? 'コピー' : 'Copy'; ?>"
                        class="btn btn-sm btn-outline-secondary flex-shrink-0">
                  <i class="ti ti-copy" aria-hidden="true"></i>
                </button>
              </div>
              <?php if ($note !== ''): ?>
                <span class="small text-muted"><?php echo htmlspecialchars($note); ?></span>
              <?php endif; ?>
            </div>
          <?php
          };
          ?>

          <div x-show="choice !== 'saml'">
            <?php $urlRow('Callback URL', $displayCallback, $lang === 'ja' ? 'IdP の Allowed Callback URLs に登録' : 'Add to IdP Allowed Callback URLs'); ?>
          </div>
          <div x-show="choice === 'saml'">
            <?php $urlRow('ACS URL', $displayAcs); ?>
          </div>
          <div x-show="choice === 'saml'">
            <?php $urlRow('SLS URL', $displaySls); ?>
          </div>
          <?php $urlRow('Login URL', $loginUrl, $lang === 'ja' ? 'ユーザーがログインするページ' : 'Page where users sign in'); ?>
        </div>
      </div>

      <!-- Provider Name (common) -->
      <?php
      ui('formField', [
        'name'        => 'name',
        'label'       => $lang === 'ja' ? 'プロバイダ名' : 'Provider Name',
        'value'       => $v->provider['name'] ?? '',
        'required'    => true,
        'placeholder' => $lang === 'ja' ? 'プロバイダ名を入力' : 'Enter provider name',
      ]);
      ?>

    <!-- Auth0 -->
    <fieldset x-show="choice === 'auth0'" x-cloak :disabled="choice !== 'auth0'" class="m-0 border-0 p-0" style="min-width:0">
      <div class="border-top pt-3 mb-3">
        <h5 class="fw-semibold mb-1"><i class="ti ti-shield-lock text-primary me-2"></i>Auth0</h5>
        <p class="small text-muted"><?php echo $lang === 'ja' ? 'Auth0 テナントの OIDC 設定' : 'Auth0 tenant OIDC configuration'; ?></p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://acme.eu.auth0.com/',
        'help'        => $lang === 'ja' ? 'Auth0 テナントのルート URL。例: https://acme.eu.auth0.com/' : 'Auth0 tenant root URL, e.g. https://acme.eu.auth0.com/',
      ]);
      ui('formField', ['name' => 'client_id', 'label' => 'Client ID', 'value' => $v->provider['client_id'] ?? '', 'placeholder' => 'your-client-id']);
      ?>
      <div class="mb-3">
        <label for="client_secret" class="form-label">
          Client Secret
          <?php if (!$v->hasSecret): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? ($lang === 'ja' ? '●●●●●●●● （変更時のみ入力）' : '●●●●●●●● (enter only to change)') : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="form-control">
        <?php if ($v->hasSecret): ?>
          <div class="form-text"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></div>
        <?php else: ?>
          <div class="form-text text-danger"><i class="ti ti-alert-triangle me-1"></i><?php echo $lang === 'ja' ? 'シークレット未設定。入力して保存するまでログインできません。' : 'No client secret stored. Sign-in will fail until a secret is saved.'; ?></div>
        <?php endif; ?>
      </div>
      <?php
      ui('formField', [
        'name'        => 'auth0_domain',
        'label'       => $lang === 'ja' ? 'Auth0 ドメイン（オプション）' : 'Auth0 Domain (optional)',
        'value'       => $cfg['domain'] ?? '',
        'placeholder' => 'acme.eu.auth0.com',
        'help'        => $lang === 'ja' ? '空欄時は Issuer URL のホストを使います。' : 'If blank, the Issuer URL host is used.',
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

    <!-- AWS Cognito -->
    <fieldset x-show="choice === 'cognito'" x-cloak :disabled="choice !== 'cognito'" class="m-0 border-0 p-0" style="min-width:0">
      <div class="border-top pt-3 mb-3">
        <h5 class="fw-semibold mb-1"><i class="ti ti-cloud text-warning me-2"></i>AWS Cognito</h5>
        <p class="small text-muted"><?php echo $lang === 'ja' ? 'Cognito ユーザープール設定' : 'Cognito User Pool configuration'; ?></p>
      </div>
      <?php
      ui('formField', ['name' => 'cognito_region',       'label' => $lang === 'ja' ? 'リージョン' : 'Region',           'value' => $cfg['region'] ?? '',        'placeholder' => 'ap-northeast-1']);
      ui('formField', ['name' => 'cognito_user_pool_id', 'label' => $lang === 'ja' ? 'ユーザープール ID' : 'User Pool ID', 'value' => $cfg['user_pool_id'] ?? '', 'placeholder' => 'ap-northeast-1_AbCdEfGhI']);
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_xxx',
        'help'        => $lang === 'ja' ? '形式: https://cognito-idp.{region}.amazonaws.com/{pool_id}' : 'Format: https://cognito-idp.{region}.amazonaws.com/{pool_id}',
      ]);
      ui('formField', ['name' => 'client_id', 'label' => 'Client ID', 'value' => $v->provider['client_id'] ?? '', 'placeholder' => 'your-app-client-id']);
      ?>
      <div class="mb-3">
        <label for="client_secret" class="form-label">
          Client Secret<?php if (!$v->hasSecret): ?><span class="text-danger ms-1" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? '●●●●●●●●' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="form-control">
        <?php if ($v->hasSecret): ?>
          <div class="form-text"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></div>
        <?php else: ?>
          <div class="form-text text-danger"><i class="ti ti-alert-triangle me-1"></i><?php echo $lang === 'ja' ? 'シークレット未設定。入力して保存するまでログインできません。' : 'No client secret stored. Sign-in will fail until a secret is saved.'; ?></div>
        <?php endif; ?>
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

    <!-- Firebase Auth -->
    <fieldset x-show="choice === 'firebase'" x-cloak :disabled="choice !== 'firebase'" class="m-0 border-0 p-0" style="min-width:0">
      <div class="border-top pt-3 mb-3">
        <h5 class="fw-semibold mb-1"><i class="ti ti-flame text-danger me-2"></i>Firebase Auth</h5>
        <p class="small text-muted">
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
        'label'       => 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://accounts.google.com',
        'help'        => $lang === 'ja' ? 'GCP OAuth 2.0 クライアントを使う場合は https://accounts.google.com' : 'Use https://accounts.google.com for GCP OAuth 2.0 clients',
      ]);
      ui('formField', [
        'name'        => 'client_id',
        'label'       => 'Client ID',
        'value'       => $v->provider['client_id'] ?? '',
        'placeholder' => 'xxxxx.apps.googleusercontent.com',
        'help'        => $lang === 'ja' ? 'GCP コンソール → OAuth 2.0 クライアント ID' : 'GCP Console → APIs & Services → Credentials → OAuth 2.0 Client ID',
      ]);
      ?>
      <div class="mb-3">
        <label for="client_secret" class="form-label">
          Client Secret<?php if (!$v->hasSecret): ?><span class="text-danger ms-1" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? '●●●●●●●●' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="form-control">
        <?php if ($v->hasSecret): ?>
          <div class="form-text"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></div>
        <?php else: ?>
          <div class="form-text text-danger"><i class="ti ti-alert-triangle me-1"></i><?php echo $lang === 'ja' ? 'シークレット未設定。入力して保存するまでログインできません。' : 'No client secret stored. Sign-in will fail until a secret is saved.'; ?></div>
        <?php endif; ?>
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

      <div class="mb-3">
        <p class="form-label fw-medium">
          <?php echo $lang === 'ja' ? 'Firebase Auth で有効にした ID プロバイダ' : 'Identity Providers enabled in Firebase Auth'; ?>
        </p>
        <p class="small text-muted mb-2">
          <?php echo $lang === 'ja'
            ? 'Firebase Authentication コンソールで有効にしているプロバイダにチェックを入れてください（参照用）。'
            : 'Check the providers you have enabled in your Firebase Authentication console (for reference).'; ?>
        </p>
        <div class="row row-cols-2 row-cols-sm-3 g-2">
          <?php foreach ($fbProviderOptions as $key => $label): ?>
            <div class="col">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="fbp_<?php echo htmlspecialchars($key); ?>"
                       name="firebase_providers[]" value="<?php echo htmlspecialchars($key); ?>"
                       <?php echo in_array($key, $fbProvidersEnabled, true) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="fbp_<?php echo htmlspecialchars($key); ?>">
                  <?php echo htmlspecialchars($label); ?>
                </label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </fieldset>

    <!-- Generic OIDC -->
    <fieldset x-show="choice === 'oidc'" x-cloak :disabled="choice !== 'oidc'" class="m-0 border-0 p-0" style="min-width:0">
      <div class="border-top pt-3 mb-3">
        <h5 class="fw-semibold mb-1"><i class="ti ti-key text-secondary me-2"></i>Generic OIDC</h5>
        <p class="small text-muted"><?php echo $lang === 'ja' ? '標準準拠の OpenID Connect プロバイダ' : 'Any standards-compliant OpenID Connect provider'; ?></p>
      </div>
      <?php
      ui('formField', [
        'name'        => 'issuer_or_metadata_url',
        'label'       => 'Issuer / Discovery URL',
        'value'       => $v->provider['issuer_or_metadata_url'] ?? '',
        'placeholder' => 'https://example.com/.well-known/openid-configuration',
        'help'        => $lang === 'ja' ? 'OIDC プロバイダの Discovery URL' : 'OIDC provider discovery URL',
      ]);
      ui('formField', ['name' => 'client_id', 'label' => 'Client ID', 'value' => $v->provider['client_id'] ?? '', 'placeholder' => 'your-client-id']);
      ?>
      <div class="mb-3">
        <label for="client_secret" class="form-label">
          Client Secret<?php if (!$v->hasSecret): ?><span class="text-danger ms-1" aria-hidden="true">*</span><?php endif; ?>
        </label>
        <input type="password" id="client_secret" name="client_secret" value=""
               placeholder="<?php echo $v->hasSecret ? '●●●●●●●●' : ($lang === 'ja' ? 'シークレットを入力' : 'Enter secret'); ?>"
               autocomplete="new-password"
               <?php if (!$v->hasSecret): ?>required<?php endif; ?>
               class="form-control">
        <?php if ($v->hasSecret): ?>
          <div class="form-text"><?php echo $lang === 'ja' ? '変更する場合のみ入力してください' : 'Leave blank to keep the current secret'; ?></div>
        <?php else: ?>
          <div class="form-text text-danger"><i class="ti ti-alert-triangle me-1"></i><?php echo $lang === 'ja' ? 'シークレット未設定。入力して保存するまでログインできません。' : 'No client secret stored. Sign-in will fail until a secret is saved.'; ?></div>
        <?php endif; ?>
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

    <!-- SAML 2.0 -->
    <fieldset x-show="choice === 'saml'" x-cloak :disabled="choice !== 'saml'" class="m-0 border-0 p-0" style="min-width:0">
      <div class="border-top pt-3 mb-3">
        <h5 class="fw-semibold mb-1"><i class="ti ti-building text-secondary me-2"></i>SAML 2.0</h5>
        <p class="small text-muted"><?php echo $lang === 'ja' ? 'エンタープライズ IdP (Okta / ADFS 等)' : 'Enterprise IdP (Okta, ADFS, etc.)'; ?></p>
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

    <!-- Advanced: claim mapping -->
    <div class="border-top pt-3 mb-4">
      <details>
        <summary class="text-uppercase small fw-semibold text-muted mb-0" style="cursor:pointer">
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

    <!-- Toggles -->
    <?php $enabledDefault = !empty($v->provider['enabled']) || !$v->hasSecret; ?>
    <div class="d-flex flex-wrap align-items-center gap-4 mb-4">
      <div class="form-check form-switch">
        <input type="checkbox" class="form-check-input" id="enabled" name="enabled" <?php echo $enabledDefault ? 'checked' : ''; ?>>
        <label class="form-check-label" for="enabled"><?php echo $lang === 'ja' ? '有効' : 'Enabled'; ?></label>
      </div>
      <div class="form-check form-switch">
        <input type="checkbox" class="form-check-input" id="is_default" name="is_default" <?php echo !empty($v->provider['is_default']) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="is_default"><?php echo $lang === 'ja' ? 'デフォルトに設定' : 'Set as Default'; ?></label>
      </div>
    </div>

    <!-- Verify Connection -->
    <div class="border-top pt-3 mb-4">
      <p class="fw-medium mb-1"><?php echo $lang === 'ja' ? '接続テスト' : 'Test Connection'; ?></p>
      <p class="small text-muted mb-3">
        <?php echo $lang === 'ja'
          ? 'Issuer URL（SAML の場合はメタデータ URL）が到達可能かどうかを確認します。'
          : 'Checks whether the Issuer URL (or SAML metadata URL) is reachable and returns a valid response.'; ?>
      </p>
      <button type="button"
              @click="verify()"
              :disabled="verifyStatus === 'loading'"
              class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
        <i class="ti ti-plug" x-show="verifyStatus !== 'loading'" aria-hidden="true"></i>
        <span x-show="verifyStatus === 'loading'" class="spinner-border spinner-border-sm" role="status"></span>
        <span x-show="verifyStatus !== 'loading'"><?php echo $lang === 'ja' ? '接続を確認する' : 'Verify Connection'; ?></span>
        <span x-show="verifyStatus === 'loading'" x-cloak><?php echo $lang === 'ja' ? '確認中...' : 'Checking…'; ?></span>
      </button>

      <div class="mt-3" x-show="verifyStatus !== null" x-cloak>
        <div x-show="verifyStatus === 'ok'" class="alert alert-success d-flex align-items-start gap-2" role="status">
          <i class="ti ti-circle-check fs-5 flex-shrink-0" aria-hidden="true"></i>
          <div x-text="verifyMsg"></div>
        </div>
        <div x-show="verifyStatus === 'error'" class="alert alert-danger d-flex align-items-start gap-2" role="alert">
          <i class="ti ti-alert-circle fs-5 flex-shrink-0" aria-hidden="true"></i>
          <div x-text="verifyMsg"></div>
        </div>
        <template x-if="verifyStatus === 'ok' && verifyAuthUrl">
          <a :href="verifyAuthUrl" target="_blank" rel="noopener noreferrer"
             class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2 mt-2">
            <i class="ti ti-external-link" aria-hidden="true"></i>
            <?php echo ui_text($lang === 'ja' ? 'テストサインインを開く →' : 'Open Test Sign-In →'); ?>
          </a>
        </template>
      </div>
    </div>

    <!-- Save -->
    <?php
    ui('button', [
      'label'      => $lang === 'ja' ? '保存する' : 'Save',
      'type'       => 'submit',
      'variant'    => 'primary',
      'extraClass' => 'w-100',
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
