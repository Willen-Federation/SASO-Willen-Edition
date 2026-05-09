<?php $this->title = '認証プロバイダーの追加'; ?>
<?php $this->content = function ($v) { ?>
<?php $csrf = \saso\util\CSRFtoken::current(); ?>

<h2 class="mb-5 text-lg font-semibold" style="color:var(--saso-text)">認証プロバイダーの追加</h2>

<?php if ($v->errorMessage !== '') { ?>
<div class="ta-alert ta-alert-danger mb-5" role="alert" aria-live="assertive">
  <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
    <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
  </svg>
  <?php echo htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php } ?>

<!-- ======================================================
     準備済みプロバイダー
====================================================== -->
<section class="mb-6">
  <h4 class="mb-2 font-semibold" style="color:var(--saso-text)">準備済みプロバイダー</h4>
  <p class="mb-4 text-sm" style="color:var(--saso-text-sub)">サービスに必要な情報を入力するだけで簡単に設定できます。</p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

    <button type="button" class="provider-card rounded-2xl border p-4 text-left transition-all hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#3c50e0]"
            style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
            data-provider="auth0" aria-pressed="false">
      <div class="mb-2 flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
        <svg class="h-4 w-4 text-[#3c50e0]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6l-8-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        Auth0
      </div>
      <p class="text-sm" style="color:var(--saso-text-sub)">
        クラウド型 IDaaS。<strong>Auth0 ドメイン</strong>・<strong>クライアント ID</strong>・<strong>クライアントシークレット</strong>の 3 項目で設定完了。
      </p>
    </button>

    <button type="button" class="provider-card rounded-2xl border p-4 text-left transition-all hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#3c50e0]"
            style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
            data-provider="cognito" aria-pressed="false">
      <div class="mb-2 flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
        <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        Amazon Cognito
      </div>
      <p class="text-sm" style="color:var(--saso-text-sub)">
        AWS マネージド認証。<strong>リージョン</strong>・<strong>ユーザープール ID</strong>・<strong>クライアント ID</strong>・<strong>クライアントシークレット</strong>で設定完了。
      </p>
    </button>

    <button type="button" class="provider-card rounded-2xl border p-4 text-left transition-all hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#3c50e0]"
            style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
            data-provider="firebase" aria-pressed="false">
      <div class="mb-2 flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
        <svg class="h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M8.6 17.8L4 9l4 3 3-7 1.3 3.4 2.7-6.4L17 9l1.5 2.8A9 9 0 0 1 12 21a9 9 0 0 1-3.4-3.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        Firebase Authentication
      </div>
      <p class="text-sm" style="color:var(--saso-text-sub)">
        Google のモバイルファースト認証。<strong>プロジェクト ID</strong>・<strong>クライアント ID</strong>・<strong>クライアントシークレット</strong>で設定完了。
      </p>
    </button>

  </div>
</section>

<!-- ======================================================
     その他のオプション（汎用 OIDC・SAML）
====================================================== -->
<section class="mb-6">
  <h4 class="mb-2 font-semibold" style="color:var(--saso-text)">その他のオプション</h4>
  <p class="mb-4 text-sm" style="color:var(--saso-text-sub)">標準プロトコルに対応した汎用プロバイダーです。</p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    <button type="button" class="provider-card rounded-2xl border p-4 text-left transition-all hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#3c50e0]"
            style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
            data-provider="oidc" aria-pressed="false">
      <div class="mb-2 flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="color:var(--saso-text-sub)">
          <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        汎用 OIDC
      </div>
      <p class="text-sm" style="color:var(--saso-text-sub)">
        OpenID Connect 対応の任意の ID プロバイダー（Keycloak・Microsoft Entra ID など）。<strong>発行者 URL</strong>・<strong>クライアント ID</strong>・<strong>クライアントシークレット</strong>を指定します。
      </p>
    </button>

    <button type="button" class="provider-card rounded-2xl border p-4 text-left transition-all hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#3c50e0]"
            style="background:var(--saso-card);border-color:var(--saso-card-bdr)"
            data-provider="saml" aria-pressed="false">
      <div class="mb-2 flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="color:var(--saso-text-sub)">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        SAML 2.0
      </div>
      <p class="text-sm" style="color:var(--saso-text-sub)">
        エンタープライズ向け SAML ID プロバイダー（Active Directory Federation Services・Shibboleth など）。<strong>メタデータ URL</strong> を指定します。
      </p>
    </button>

  </div>
</section>

<!-- ======================================================
     設定フォーム（選択後に表示）
====================================================== -->

<div id="provider-forms" class="hidden mt-6">
  <hr class="mb-6" style="border-color:var(--saso-card-bdr)">

  <!-- Auth0 フォーム -->
  <div id="form-auth0" class="provider-form hidden">
    <div class="rounded-2xl border overflow-hidden mb-2"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h5 class="flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
          <svg class="h-4 w-4 text-[#3c50e0]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6l-8-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          </svg>
          Auth0 の設定
        </h5>
      </div>
      <div class="px-5 py-5">
        <form method="post" novalidate>
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="provider_template" value="auth0">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="auth0-provider-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">プロバイダー名 <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="auth0-provider-name" name="provider_name"
                     placeholder="例: Auth0 本番" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">ログイン画面に表示される名前です。</p>
            </div>
            <div>
              <label for="auth0-domain" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">Auth0 ドメイン <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="auth0-domain" name="auth0_domain"
                     placeholder="例: example.auth0.com" required aria-required="true"
                     autocomplete="off" inputmode="url"
                     pattern="(?:https?://)?[A-Za-z0-9.\-]+(?:\.[A-Za-z]{2,})+/?.*">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)"><code>https://</code> は省略可。貼り付けた場合は自動で除去されます。</p>
              <div id="auth0-domain-feedback" class="mt-1 text-xs" style="color:var(--saso-text-sub)"></div>
            </div>
            <div>
              <label for="auth0-client-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアント ID <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="auth0-client-id" name="client_id"
                     placeholder="Auth0 アプリのクライアント ID" required aria-required="true">
            </div>
            <div>
              <label for="auth0-client-secret" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアントシークレット <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="password" class="form-input w-full" id="auth0-client-secret" name="client_secret"
                     placeholder="Auth0 アプリのクライアントシークレット" required aria-required="true">
            </div>
            <div class="flex flex-wrap gap-2 sm:col-span-2">
              <button type="submit" class="btn btn-primary">保存</button>
              <button type="button" class="btn btn-secondary" data-test-connection="auth0">接続をテスト</button>
              <button type="button" class="btn btn-secondary" onclick="clearSelection()">キャンセル</button>
              <span id="auth0-test-result" class="self-center text-sm"></span>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Amazon Cognito フォーム -->
  <div id="form-cognito" class="provider-form hidden">
    <div class="rounded-2xl border overflow-hidden mb-2"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h5 class="flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
          <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          </svg>
          Amazon Cognito の設定
        </h5>
      </div>
      <div class="px-5 py-5">
        <form method="post">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="provider_template" value="cognito">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="sm:col-span-2">
              <label for="cognito-provider-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">プロバイダー名 <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="cognito-provider-name" name="provider_name"
                     placeholder="例: Cognito 本番" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">ログイン画面に表示される名前です。</p>
            </div>
            <div>
              <label for="cognito-region" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">リージョン <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="cognito-region" name="cognito_region"
                     placeholder="例: ap-northeast-1" required aria-required="true">
            </div>
            <div>
              <label for="cognito-user-pool-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">ユーザープール ID <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="cognito-user-pool-id" name="cognito_user_pool_id"
                     placeholder="例: ap-northeast-1_XXXXXXXXX" required aria-required="true">
            </div>
            <div class="sm:col-span-2">
              <label for="cognito-client-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアント ID <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="cognito-client-id" name="client_id"
                     placeholder="Cognito アプリクライアントのクライアント ID" required aria-required="true">
            </div>
            <div class="sm:col-span-2">
              <label for="cognito-client-secret" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアントシークレット</label>
              <input type="password" class="form-input w-full" id="cognito-client-secret" name="client_secret"
                     placeholder="アプリクライアントのシークレット（省略可）">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">アプリクライアントにシークレットが設定されている場合のみ入力。</p>
            </div>
            <div class="flex gap-2 sm:col-span-4">
              <button type="submit" class="btn btn-primary">保存</button>
              <button type="button" class="btn btn-secondary" onclick="clearSelection()">キャンセル</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Firebase Authentication フォーム -->
  <div id="form-firebase" class="provider-form hidden">
    <div class="rounded-2xl border overflow-hidden mb-2"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h5 class="flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
          <svg class="h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8.6 17.8L4 9l4 3 3-7 1.3 3.4 2.7-6.4L17 9l1.5 2.8A9 9 0 0 1 12 21a9 9 0 0 1-3.4-3.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          </svg>
          Firebase Authentication の設定
        </h5>
      </div>
      <div class="px-5 py-5">
        <form method="post">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="provider_template" value="firebase">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="firebase-provider-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">プロバイダー名 <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="firebase-provider-name" name="provider_name"
                     placeholder="例: Firebase 本番" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">ログイン画面に表示される名前です。</p>
            </div>
            <div>
              <label for="firebase-project-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">Firebase プロジェクト ID <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="firebase-project-id" name="firebase_project_id"
                     placeholder="例: my-project-12345" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">Firebase コンソールのプロジェクト ID。</p>
            </div>
            <div>
              <label for="firebase-client-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアント ID <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="firebase-client-id" name="client_id"
                     placeholder="Firebase Web API キー" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">Firebase コンソールのプロジェクト設定 → Web API キー。</p>
            </div>
            <div>
              <label for="firebase-client-secret" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアントシークレット</label>
              <input type="password" class="form-input w-full" id="firebase-client-secret" name="client_secret"
                     placeholder="（省略可）">
            </div>
            <div class="flex gap-2 sm:col-span-2">
              <button type="submit" class="btn btn-primary">保存</button>
              <button type="button" class="btn btn-secondary" onclick="clearSelection()">キャンセル</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 汎用 OIDC フォーム -->
  <div id="form-oidc" class="provider-form hidden">
    <div class="rounded-2xl border overflow-hidden mb-2"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h5 class="flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="color:var(--saso-text-sub)">
            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          汎用 OIDC の設定
        </h5>
      </div>
      <div class="px-5 py-5">
        <form method="post">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="provider_template" value="oidc">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="oidc-provider-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">プロバイダー名 <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="oidc-provider-name" name="provider_name"
                     placeholder="例: Keycloak 本番" required aria-required="true">
            </div>
            <div>
              <label for="oidc-issuer-url" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">発行者 URL (Issuer URL) <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="url" class="form-input w-full" id="oidc-issuer-url" name="oidc_issuer_url"
                     placeholder="例: https://sso.example.com/realms/my-realm" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)"><code>/.well-known/openid-configuration</code> の親 URL。</p>
            </div>
            <div>
              <label for="oidc-client-id" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアント ID <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="oidc-client-id" name="client_id"
                     placeholder="OIDC クライアント ID" required aria-required="true">
            </div>
            <div>
              <label for="oidc-client-secret" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">クライアントシークレット</label>
              <input type="password" class="form-input w-full" id="oidc-client-secret" name="client_secret"
                     placeholder="（省略可）">
            </div>
            <div>
              <label for="oidc-scopes" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">追加スコープ</label>
              <input type="text" class="form-input w-full" id="oidc-scopes" name="scopes"
                     placeholder="例: offline_access profile">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">スペース区切り。<code>openid</code> は自動で付与されます。</p>
            </div>
            <div class="flex gap-2 sm:col-span-2">
              <button type="submit" class="btn btn-primary">保存</button>
              <button type="button" class="btn btn-secondary" onclick="clearSelection()">キャンセル</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- SAML 2.0 フォーム -->
  <div id="form-saml" class="provider-form hidden">
    <div class="rounded-2xl border overflow-hidden mb-2"
         style="background:var(--saso-card);border-color:var(--saso-card-bdr)">
      <div class="border-b px-5 py-4" style="border-color:var(--saso-card-bdr)">
        <h5 class="flex items-center gap-2 font-semibold" style="color:var(--saso-text)">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="color:var(--saso-text-sub)">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
            <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          </svg>
          SAML 2.0 の設定
        </h5>
      </div>
      <div class="px-5 py-5">
        <form method="post">
          <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="provider_template" value="saml">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="saml-provider-name" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">プロバイダー名 <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="text" class="form-input w-full" id="saml-provider-name" name="provider_name"
                     placeholder="例: Active Directory" required aria-required="true">
            </div>
            <div>
              <label for="saml-metadata-url" class="mb-1.5 block text-sm font-medium" style="color:var(--saso-text)">メタデータ URL <span class="text-red-500" aria-hidden="true">*</span></label>
              <input type="url" class="form-input w-full" id="saml-metadata-url" name="saml_metadata_url"
                     placeholder="例: https://idp.example.com/FederationMetadata/2007-06/FederationMetadata.xml" required aria-required="true">
              <p class="mt-1 text-xs" style="color:var(--saso-text-sub)">IdP のフェデレーションメタデータ XML の URL。</p>
            </div>
            <div class="flex gap-2 sm:col-span-2">
              <button type="submit" class="btn btn-primary">保存</button>
              <button type="button" class="btn btn-secondary" onclick="clearSelection()">キャンセル</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

</div><!-- /#provider-forms -->

<script>
(function () {
  function selectProvider(name) {
    document.querySelectorAll('.provider-card').forEach(function (card) {
      var isSelected = card.dataset.provider === name;
      card.classList.toggle('border-[#3c50e0]', isSelected);
      card.classList.toggle('shadow-md', isSelected);
      card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });

    document.querySelectorAll('.provider-form').forEach(function (form) {
      form.classList.add('hidden');
    });

    var target = document.getElementById('form-' + name);
    if (target) {
      document.getElementById('provider-forms').classList.remove('hidden');
      target.classList.remove('hidden');
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  window.clearSelection = function () {
    document.querySelectorAll('.provider-card').forEach(function (card) {
      card.classList.remove('border-[#3c50e0]', 'shadow-md');
      card.setAttribute('aria-pressed', 'false');
    });
    document.querySelectorAll('.provider-form').forEach(function (f) {
      f.classList.add('hidden');
    });
    document.getElementById('provider-forms').classList.add('hidden');
  };

  document.querySelectorAll('.provider-card').forEach(function (card) {
    card.addEventListener('click', function () {
      selectProvider(card.dataset.provider);
    });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        selectProvider(card.dataset.provider);
      }
    });
  });

  // Auth0 domain normalisation
  function normaliseAuth0Domain(value) {
    if (typeof value !== 'string') return '';
    var v = value.trim();
    v = v.replace(/^[a-zA-Z][a-zA-Z0-9+.\-]*:\/\//, '');
    v = v.replace(/^\/+|\s+$/g, '').replace(/\/+$/g, '');
    return v;
  }

  var auth0Domain = document.getElementById('auth0-domain');
  var auth0Feedback = document.getElementById('auth0-domain-feedback');
  if (auth0Domain) {
    auth0Domain.addEventListener('blur', function () {
      var cleaned = normaliseAuth0Domain(auth0Domain.value);
      if (cleaned !== auth0Domain.value) {
        auth0Domain.value = cleaned;
      }
      if (auth0Feedback) {
        auth0Feedback.textContent = cleaned ? '保存される発行者: https://' + cleaned : '';
      }
    });
    auth0Domain.addEventListener('paste', function (e) {
      var pasted = (e.clipboardData || window.clipboardData).getData('text');
      var cleaned = normaliseAuth0Domain(pasted);
      if (cleaned && cleaned !== pasted) {
        e.preventDefault();
        auth0Domain.value = cleaned;
        if (auth0Feedback) {
          auth0Feedback.textContent = '貼り付け値から https:// を除去しました: https://' + cleaned;
        }
      }
    });
  }

  // Server-side test-connection probe
  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  document.querySelectorAll('[data-test-connection]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var flavor = btn.getAttribute('data-test-connection');
      var form = btn.closest('form');
      var resultEl = form.querySelector('#' + flavor + '-test-result');

      var auth0Input = form.querySelector('[name=auth0_domain]');
      if (auth0Input) {
        auth0Input.value = normaliseAuth0Domain(auth0Input.value);
      }

      if (resultEl) resultEl.innerHTML = '<span style="color:var(--saso-text-sub)">接続を確認中…</span>';
      btn.disabled = true;
      var t0 = Date.now();

      var fd = new FormData(form);
      fetch('?action=test', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json' },
      })
        .then(function (r) { return r.json().then(function (j) { return { status: r.status, json: j }; }); })
        .then(function (resp) {
          var ms = Date.now() - t0;
          var msg = (resp.json && resp.json.message) ? resp.json.message : ('HTTP ' + resp.status);
          if (resp.json && resp.json.ok) {
            if (resultEl) resultEl.innerHTML = '<span class="text-green-600">✓ ' + escapeHtml(msg) + ' (' + ms + 'ms)</span>';
          } else {
            if (resultEl) resultEl.innerHTML = '<span class="text-red-500">✗ ' + escapeHtml(msg) + '</span>';
          }
        })
        .catch(function (err) {
          if (resultEl) resultEl.innerHTML = '<span class="text-red-500">接続できません: ' + escapeHtml(err && err.message ? err.message : 'ネットワークエラー') + '</span>';
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  });
}());
</script>

<?php }; ?>
