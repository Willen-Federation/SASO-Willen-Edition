<?php $this->content = function ($v) { ?>

<?php $lang = $_SESSION['lang'] ?? 'ja'; ?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => $lang === 'ja' ? '管理者権限が必要です' : 'Admin access required',
    'body'    => $lang === 'ja' ? '認証プロバイダを管理するには role=admin のユーザーでサインインしてください。' : 'Sign in as a user with role=admin to manage authentication providers.',
  ]); ?>
<?php } else { ?>

<?php
  ui('card', [
    'title'   => $lang === 'ja' ? '認証プロバイダの種類を選択' : 'Choose a provider type',
    'actions' => function () use ($lang) {
        ui('button', [
            'label'   => $lang === 'ja' ? '一覧に戻る' : 'Back to list',
            'href'    => './auth/providers/',
            'type'    => 'link',
            'variant' => 'secondary',
        ]);
    },
    'body' => function () use ($lang) {
?>

  <p class="mb-4 small text-muted">
    <?php echo $lang === 'ja'
      ? '連携する外部 IdP の種類を選んでください。次のステップで接続情報とセットアップ手順を案内します。'
      : 'Select the type of identity provider you want to connect. The next step shows setup instructions and the fields to fill in.'; ?>
  </p>

  <div class="row row-cols-1 row-cols-sm-2 g-3">

    <?php
    $providers = [
      'auth0'   => [
        'label' => 'Auth0',
        'tag'   => 'SaaS IdP',
        'desc'  => $lang === 'ja'
          ? 'Universal Login、PKCE、MFA、ソーシャルログインをサポートする管理 IdP。'
          : 'Managed identity platform with Universal Login, PKCE, MFA, and social login support.',
        'icon'  => 'ti ti-shield-lock',
      ],
      'cognito' => [
        'label' => 'AWS Cognito',
        'tag'   => 'AWS',
        'desc'  => $lang === 'ja'
          ? 'AWS マネージドユーザープール。Hosted UI で OIDC フローを提供。'
          : 'AWS managed user pools with Hosted UI for OIDC-based authentication.',
        'icon'  => 'ti ti-cloud',
      ],
      'saml'    => [
        'label' => 'SAML 2.0',
        'tag'   => 'Enterprise SSO',
        'desc'  => $lang === 'ja'
          ? 'Okta、Azure AD、OneLogin など企業向け SAML IdP との SSO。'
          : 'Enterprise SSO with Okta, Azure AD, OneLogin, and any SAML 2.0-compliant IdP.',
        'icon'  => 'ti ti-building',
      ],
      'oidc'    => [
        'label' => 'Generic OIDC',
        'tag'   => 'OpenID Connect',
        'desc'  => $lang === 'ja'
          ? 'Keycloak、Okta、Microsoft Entra、その他 OpenID Connect 準拠 IdP。'
          : 'Any OpenID Connect-compliant provider: Keycloak, Okta, Microsoft Entra, and more.',
        'icon'  => 'ti ti-key',
      ],
    ];

    foreach ($providers as $key => $p):
    ?>
    <div class="col">
      <a href="./auth/provider/new/<?php echo $key; ?>"
         class="card card-link card-link-pop h-100 text-decoration-none">
        <div class="card-body d-flex flex-column gap-2">
          <div class="d-flex align-items-start justify-content-between">
            <i class="<?php echo $p['icon']; ?> fs-2 text-primary" aria-hidden="true"></i>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($p['tag']); ?></span>
          </div>
          <div>
            <h4 class="card-title mb-1"><?php echo htmlspecialchars($p['label']); ?></h4>
            <p class="small text-muted mb-0"><?php echo htmlspecialchars($p['desc']); ?></p>
          </div>
          <div class="mt-auto">
            <span class="small text-primary fw-medium">
              <?php echo $lang === 'ja' ? 'セットアップ →' : 'Set up →'; ?>
            </span>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>

  </div>

<?php
    },
  ]);
?>

<?php } ?>

<?php }; ?>
