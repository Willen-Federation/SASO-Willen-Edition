<?php $this->title = '認証プロバイダーの追加'; ?>
<?php $this->content = function ($v) { ?>
<?php $csrf = \saso\util\CSRFtoken::current(); ?>

<h2 class="mb-4">認証プロバイダーの追加</h2>

<?php if ($v->errorMessage !== '') { ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($v->errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<!-- ======================================================
     準備済みプロバイダー
     各サービスに必要な情報を入力するだけで設定完了
====================================================== -->
<section class="mb-4">
  <h4 class="mb-3">準備済みプロバイダー</h4>
  <p class="text-muted small mb-3">サービスに必要な情報を入力するだけで簡単に設定できます。</p>
  <div class="row g-3">

    <div class="col-md-4">
      <div class="card h-100 provider-card" data-provider="auth0" role="button" tabindex="0"
           aria-pressed="false" style="cursor:pointer;">
        <div class="card-body">
          <h6 class="card-title d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock-fill text-primary"></i> Auth0
          </h6>
          <p class="card-text text-muted small">
            クラウド型 IDaaS。<strong>Auth0 ドメイン</strong>・<strong>クライアント ID</strong>・
            <strong>クライアントシークレット</strong>の 3 項目で設定完了。
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 provider-card" data-provider="cognito" role="button" tabindex="0"
           aria-pressed="false" style="cursor:pointer;">
        <div class="card-body">
          <h6 class="card-title d-flex align-items-center gap-2">
            <i class="bi bi-cloud-fill text-warning"></i> Amazon Cognito
          </h6>
          <p class="card-text text-muted small">
            AWS マネージド認証。<strong>リージョン</strong>・<strong>ユーザープール ID</strong>・
            <strong>クライアント ID</strong>・<strong>クライアントシークレット</strong>で設定完了.
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 provider-card" data-provider="firebase" role="button" tabindex="0"
           aria-pressed="false" style="cursor:pointer;">
        <div class="card-body">
          <h6 class="card-title d-flex align-items-center gap-2">
            <i class="bi bi-fire text-danger"></i> Firebase Authentication
          </h6>
          <p class="card-text text-muted small">
            Google のモバイルファースト認証。<strong>プロジェクト ID</strong>・
            <strong>クライアント ID</strong>・<strong>クライアントシークレット</strong>で設定完了。
          </p>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ======================================================
     その他のオプション（汎用 OIDC・SAML）
====================================================== -->
<section class="mb-4">
  <h4 class="mb-3">その他のオプション</h4>
  <p class="text-muted small mb-3">標準プロトコルに対応した汎用プロバイダーです。</p>
  <div class="row g-3">

    <div class="col-md-6">
      <div class="card h-100 provider-card" data-provider="oidc" role="button" tabindex="0"
           aria-pressed="false" style="cursor:pointer;">
        <div class="card-body">
          <h6 class="card-title d-flex align-items-center gap-2">
            <i class="bi bi-key-fill text-secondary"></i> 汎用 OIDC
          </h6>
          <p class="card-text text-muted small">
            OpenID Connect 対応の任意の ID プロバイダー（Keycloak・Microsoft Entra ID など）。
            <strong>発行者 URL</strong>・<strong>クライアント ID</strong>・
            <strong>クライアントシークレット</strong>を指定します。
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card h-100 provider-card" data-provider="saml" role="button" tabindex="0"
           aria-pressed="false" style="cursor:pointer;">
        <div class="card-body">
          <h6 class="card-title d-flex align-items-center gap-2">
            <i class="bi bi-building-fill text-secondary"></i> SAML 2.0
          </h6>
          <p class="card-text text-muted small">
            エンタープライズ向け SAML ID プロバイダー（Active Directory Federation Services・Shibboleth など）。
            <strong>メタデータ URL</strong> を指定します。
          </p>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ======================================================
     設定フォーム（選択後に表示）
====================================================== -->

<div id="provider-forms" class="d-none mt-4">
  <hr>

  <!-- Auth0 フォーム -->
  <div id="form-auth0" class="provider-form d-none">
    <h5 class="mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Auth0 の設定</h5>
    <form method="post" novalidate>
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="provider_template" value="auth0">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="auth0-provider-name" class="form-label">プロバイダー名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="auth0-provider-name" name="provider_name"
                 placeholder="例: Auth0 本番" required>
          <div class="form-text">ログイン画面に表示される名前です。</div>
        </div>
        <div class="col-md-6">
          <label for="auth0-domain" class="form-label">Auth0 ドメイン <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="auth0-domain" name="auth0_domain"
                 placeholder="例: example.auth0.com" required
                 autocomplete="off" inputmode="url"
                 pattern="(?:https?://)?[A-Za-z0-9.\-]+(?:\.[A-Za-z]{2,})+/?.*">
          <div class="form-text"><code>https://</code> は省略可。貼り付けた場合は自動で除去されます。</div>
          <div id="auth0-domain-feedback" class="small text-muted mt-1"></div>
        </div>
        <div class="col-md-6">
          <label for="auth0-client-id" class="form-label">クライアント ID <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="auth0-client-id" name="client_id"
                 placeholder="Auth0 アプリのクライアント ID" required>
        </div>
        <div class="col-md-6">
          <label for="auth0-client-secret" class="form-label">クライアントシークレット <span class="text-danger">*</span></label>
          <input type="password" class="form-control" id="auth0-client-secret" name="client_secret"
                 placeholder="Auth0 アプリのクライアントシークレット" required>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary">保存</button>
          <button type="button" class="btn btn-outline-primary" data-test-connection="auth0">接続をテスト</button>
          <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">キャンセル</button>
          <span id="auth0-test-result" class="align-self-center small"></span>
        </div>
      </div>
    </form>
  </div>

  <!-- Amazon Cognito フォーム -->
  <div id="form-cognito" class="provider-form d-none">
    <h5 class="mb-3"><i class="bi bi-cloud-fill text-warning me-2"></i>Amazon Cognito の設定</h5>
    <form method="post">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="provider_template" value="cognito">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="cognito-provider-name" class="form-label">プロバイダー名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="cognito-provider-name" name="provider_name"
                 placeholder="例: Cognito 本番" required>
          <div class="form-text">ログイン画面に表示される名前です。</div>
        </div>
        <div class="col-md-3">
          <label for="cognito-region" class="form-label">リージョン <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="cognito-region" name="cognito_region"
                 placeholder="例: ap-northeast-1" required>
        </div>
        <div class="col-md-3">
          <label for="cognito-user-pool-id" class="form-label">ユーザープール ID <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="cognito-user-pool-id" name="cognito_user_pool_id"
                 placeholder="例: ap-northeast-1_XXXXXXXXX" required>
        </div>
        <div class="col-md-6">
          <label for="cognito-client-id" class="form-label">クライアント ID <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="cognito-client-id" name="client_id"
                 placeholder="Cognito アプリクライアントのクライアント ID" required>
        </div>
        <div class="col-md-6">
          <label for="cognito-client-secret" class="form-label">クライアントシークレット</label>
          <input type="password" class="form-control" id="cognito-client-secret" name="client_secret"
                 placeholder="アプリクライアントのシークレット（省略可）">
          <div class="form-text">アプリクライアントにシークレットが設定されている場合のみ入力。</div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">保存</button>
          <button type="button" class="btn btn-outline-secondary ms-2" onclick="clearSelection()">キャンセル</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Firebase Authentication フォーム -->
  <div id="form-firebase" class="provider-form d-none">
    <h5 class="mb-3"><i class="bi bi-fire text-danger me-2"></i>Firebase Authentication の設定</h5>
    <form method="post">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="provider_template" value="firebase">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="firebase-provider-name" class="form-label">プロバイダー名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="firebase-provider-name" name="provider_name"
                 placeholder="例: Firebase 本番" required>
          <div class="form-text">ログイン画面に表示される名前です。</div>
        </div>
        <div class="col-md-6">
          <label for="firebase-project-id" class="form-label">Firebase プロジェクト ID <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="firebase-project-id" name="firebase_project_id"
                 placeholder="例: my-project-12345" required>
          <div class="form-text">Firebase コンソールのプロジェクト ID。</div>
        </div>
        <div class="col-md-6">
          <label for="firebase-client-id" class="form-label">クライアント ID <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="firebase-client-id" name="client_id"
                 placeholder="Firebase Web API キー" required>
          <div class="form-text">Firebase コンソールのプロジェクト設定 → Web API キー。</div>
        </div>
        <div class="col-md-6">
          <label for="firebase-client-secret" class="form-label">クライアントシークレット</label>
          <input type="password" class="form-control" id="firebase-client-secret" name="client_secret"
                 placeholder="（省略可）">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">保存</button>
          <button type="button" class="btn btn-outline-secondary ms-2" onclick="clearSelection()">キャンセル</button>
        </div>
      </div>
    </form>
  </div>

  <!-- 汎用 OIDC フォーム -->
  <div id="form-oidc" class="provider-form d-none">
    <h5 class="mb-3"><i class="bi bi-key-fill text-secondary me-2"></i>汎用 OIDC の設定</h5>
    <form method="post">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="provider_template" value="oidc">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="oidc-provider-name" class="form-label">プロバイダー名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="oidc-provider-name" name="provider_name"
                 placeholder="例: Keycloak 本番" required>
        </div>
        <div class="col-md-6">
          <label for="oidc-issuer-url" class="form-label">発行者 URL (Issuer URL) <span class="text-danger">*</span></label>
          <input type="url" class="form-control" id="oidc-issuer-url" name="oidc_issuer_url"
                 placeholder="例: https://sso.example.com/realms/my-realm" required>
          <div class="form-text"><code>/.well-known/openid-configuration</code> の親 URL。</div>
        </div>
        <div class="col-md-6">
          <label for="oidc-client-id" class="form-label">クライアント ID <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="oidc-client-id" name="client_id"
                 placeholder="OIDC クライアント ID" required>
        </div>
        <div class="col-md-6">
          <label for="oidc-client-secret" class="form-label">クライアントシークレット</label>
          <input type="password" class="form-control" id="oidc-client-secret" name="client_secret"
                 placeholder="（省略可）">
        </div>
        <div class="col-md-6">
          <label for="oidc-scopes" class="form-label">追加スコープ</label>
          <input type="text" class="form-control" id="oidc-scopes" name="scopes"
                 placeholder="例: offline_access profile">
          <div class="form-text">スペース区切り。<code>openid</code> は自動で付与されます。</div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">保存</button>
          <button type="button" class="btn btn-outline-secondary ms-2" onclick="clearSelection()">キャンセル</button>
        </div>
      </div>
    </form>
  </div>

  <!-- SAML 2.0 フォーム -->
  <div id="form-saml" class="provider-form d-none">
    <h5 class="mb-3"><i class="bi bi-building-fill text-secondary me-2"></i>SAML 2.0 の設定</h5>
    <form method="post">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="provider_template" value="saml">
      <div class="row g-3">
        <div class="col-md-6">
          <label for="saml-provider-name" class="form-label">プロバイダー名 <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="saml-provider-name" name="provider_name"
                 placeholder="例: Active Directory" required>
        </div>
        <div class="col-md-6">
          <label for="saml-metadata-url" class="form-label">メタデータ URL <span class="text-danger">*</span></label>
          <input type="url" class="form-control" id="saml-metadata-url" name="saml_metadata_url"
                 placeholder="例: https://idp.example.com/FederationMetadata/2007-06/FederationMetadata.xml" required>
          <div class="form-text">IdP のフェデレーションメタデータ XML の URL。</div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">保存</button>
          <button type="button" class="btn btn-outline-secondary ms-2" onclick="clearSelection()">キャンセル</button>
        </div>
      </div>
    </form>
  </div>

</div><!-- /#provider-forms -->

<script>
(function () {
  function selectProvider(name) {
    document.querySelectorAll('.provider-card').forEach(function (card) {
      var isSelected = card.dataset.provider === name;
      card.classList.toggle('border-primary', isSelected);
      card.classList.toggle('shadow-sm', isSelected);
      card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });

    document.querySelectorAll('.provider-form').forEach(function (form) {
      form.classList.add('d-none');
    });

    var target = document.getElementById('form-' + name);
    if (target) {
      document.getElementById('provider-forms').classList.remove('d-none');
      target.classList.remove('d-none');
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  window.clearSelection = function () {
    document.querySelectorAll('.provider-card').forEach(function (card) {
      card.classList.remove('border-primary', 'shadow-sm');
      card.setAttribute('aria-pressed', 'false');
    });
    document.querySelectorAll('.provider-form').forEach(function (f) {
      f.classList.add('d-none');
    });
    document.getElementById('provider-forms').classList.add('d-none');
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

  // ----------------------------------------------------------------------
  //  Auth0 domain normalisation
  //
  //  Operators routinely paste "https://example.auth0.com" into the Domain
  //  field even though the placeholder shows it without a scheme. The
  //  server strips the scheme defensively (cf. ProviderSaveController), but
  //  doing it on the client too gives instant feedback and stops Safari
  //  from rejecting the submit with "The string did not match the expected
  //  pattern" — the symptom the user reported as "unexpected syntax".
  // ----------------------------------------------------------------------
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

  // ----------------------------------------------------------------------
  //  Server-side test-connection probe
  //
  //  Posts the relevant form fields to the wizard's `?action=test`
  //  endpoint, which performs the OIDC discovery (or SAML metadata) fetch
  //  server-side and validates the document. This replaces an earlier
  //  client-side `fetch(..., { mode: 'no-cors' })` that returned an opaque
  //  response with no body and status 0 — operators reported it both as
  //  spurious failures *and* spurious successes.
  // ----------------------------------------------------------------------
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

      // Run the same client-side normalisation the server uses, so the
      // value we POST matches the value the user is about to save.
      var auth0Input = form.querySelector('[name=auth0_domain]');
      if (auth0Input) {
        auth0Input.value = normaliseAuth0Domain(auth0Input.value);
      }

      if (resultEl) resultEl.innerHTML = '<span class="text-muted">接続を確認中…</span>';
      btn.disabled = true;
      var t0 = Date.now();

      var fd = new FormData(form);
      // FormData includes every field on the form. The CSRF token hidden
      // input is already on the form, so it ships automatically.
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
            if (resultEl) resultEl.innerHTML = '<span class="text-success">✓ ' + escapeHtml(msg) + ' (' + ms + 'ms)</span>';
          } else {
            if (resultEl) resultEl.innerHTML = '<span class="text-danger">✗ ' + escapeHtml(msg) + '</span>';
          }
        })
        .catch(function (err) {
          if (resultEl) resultEl.innerHTML = '<span class="text-danger">接続できません: ' + escapeHtml(err && err.message ? err.message : 'ネットワークエラー') + '</span>';
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  });
}());
</script>

<?php }; ?>
