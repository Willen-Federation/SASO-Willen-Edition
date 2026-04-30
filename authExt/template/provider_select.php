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

  <p class="mb-6 text-sm text-bodydark2">
    <?php echo $lang === 'ja'
      ? '連携する外部 IdP の種類を選んでください。次のステップで接続情報とセットアップ手順を案内します。'
      : 'Select the type of identity provider you want to connect. The next step shows setup instructions and the fields to fill in.'; ?>
  </p>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    <?php
    $providers = [
      'auth0'   => [
        'label' => 'Auth0',
        'tag'   => 'SaaS IdP',
        'desc'  => $lang === 'ja'
          ? 'Universal Login、PKCE、MFA、ソーシャルログインをサポートする管理 IdP。'
          : 'Managed identity platform with Universal Login, PKCE, MFA, and social login support.',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor"><path d="M21.98 7.448 19.819 0H4.18L2.02 7.448a11.995 11.995 0 0 0 4.599 13.034L12 24l5.381-3.518A11.995 11.995 0 0 0 21.98 7.448zm-9.98 12.01-3.53-2.308a8.07 8.07 0 0 1-3.096-8.774l.001-.004L7.62 4.204H12v15.254zm7.626-11.082a8.07 8.07 0 0 1-3.096 8.774L13 19.458V4.204h4.374l2.252 4.172z"/></svg>',
      ],
      'cognito' => [
        'label' => 'AWS Cognito',
        'tag'   => 'AWS',
        'desc'  => $lang === 'ja'
          ? 'AWS マネージドユーザープール。Hosted UI で OIDC フローを提供。'
          : 'AWS managed user pools with Hosted UI for OIDC-based authentication.',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor"><path d="M13.234 15.878 12 16.891l-1.234-1.013L9 14.473V9.527l1.766-1.405L12 7.11l1.234 1.013L15 9.527v4.946zM24 12 12 0 0 12l12 12zm-9-2.473-1.766 1.405L12 11.946l-1.234-1.013L9 9.527V8h6zM9 16v-1.527l1.766-1.405L12 14.08l1.234-1.013L15 14.473V16z"/></svg>',
      ],
      'saml'    => [
        'label' => 'SAML 2.0',
        'tag'   => 'Enterprise SSO',
        'desc'  => $lang === 'ja'
          ? 'Okta、Azure AD、OneLogin など企業向け SAML IdP との SSO。'
          : 'Enterprise SSO with Okta, Azure AD, OneLogin, and any SAML 2.0-compliant IdP.',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.4-2.33 6.79-5 7.93-2.67-1.14-5-4.53-5-7.93V7.18L12 5z"/></svg>',
      ],
      'oidc'    => [
        'label' => 'Generic OIDC',
        'tag'   => 'OpenID Connect',
        'desc'  => $lang === 'ja'
          ? 'Keycloak、Okta、Microsoft Entra、その他 OpenID Connect 準拠 IdP。'
          : 'Any OpenID Connect-compliant provider: Keycloak, Okta, Microsoft Entra, and more.',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-8 w-8" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
      ],
    ];

    foreach ($providers as $key => $p) {
        echo '<a href="./auth/provider/new/' . $key . '" class="group flex flex-col gap-3 rounded-xl border-2 border-stroke p-6 transition-all hover:border-primary hover:shadow-theme-md dark:border-strokedark dark:hover:border-primary">';
        echo '<div class="flex items-start justify-between">';
        echo '<span class="text-primary dark:text-brand-400">' . $p['icon'] . '</span>';
        echo '<span class="rounded-full bg-gray-2 px-2.5 py-0.5 text-xs font-medium text-bodydark2 dark:bg-meta-4 dark:text-white">' . htmlspecialchars($p['tag']) . '</span>';
        echo '</div>';
        echo '<div>';
        echo '<h3 class="text-lg font-semibold text-black group-hover:text-primary dark:text-white">' . htmlspecialchars($p['label']) . '</h3>';
        echo '<p class="mt-1 text-sm text-bodydark2">' . htmlspecialchars($p['desc']) . '</p>';
        echo '</div>';
        echo '<div class="flex items-center gap-1 text-sm font-medium text-primary">';
        echo '<span>' . ($lang === 'ja' ? 'セットアップ →' : 'Set up →') . '</span>';
        echo '</div>';
        echo '</a>';
    }
    ?>

  </div>

<?php
    },
  ]);
?>

<?php } ?>

<?php }; ?>
