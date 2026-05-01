<?php
$this->title = __('ui.auth.login_title', [], null, 'Sign in');
?>
<?php $this->content = function ($v) { ?>

<div class="mx-auto max-w-md">
  <?php
    ui('card', [
      'body' => function () use ($v) {
  ?>
    <h2 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">
      <?php echo ui_text(__('ui.auth.login_title', [], null, 'Sign in')); ?>
    </h2>
    <p class="mb-6 text-theme-sm text-gray-500 dark:text-gray-400">
      <?php echo ui_text(__('ui.auth.login_subtitle', [], null, 'Use your credentials or sign in with an external identity provider.')); ?>
    </p>

    <?php if ($v->isError) { ?>
      <?php ui('alert', [
        'variant' => 'danger',
        'body'    => __('ui.auth.invalid_credentials', [], null, 'ID or password is incorrect.'),
      ]); ?>
    <?php } ?>

    <?php if ($v->providerError) { ?>
      <?php ui('alert', [
        'variant' => 'danger',
        'title'   => __('error.SASO-AUTH-1006.title', [], null, 'Authentication provider is misconfigured'),
        'body'    => __('error.SASO-AUTH-1006.detail', [], null, 'This authentication provider is currently unavailable. Please ask an administrator to verify the configuration.'),
      ]); ?>
    <?php } ?>

    <?php $providers = property_exists($v, 'idpProviders') ? $v->idpProviders : []; ?>

    <?php if (!empty($providers)) { ?>
      <ul class="mb-6 flex flex-col gap-2">
        <?php foreach ($providers as $p) { ?>
          <li>
            <a href="./auth/start/<?php echo ui_attr((string) $p['id']); ?>"
               class="btn btn-secondary w-full justify-start">
              <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-xs font-semibold dark:bg-brand-500/15 dark:text-brand-400">
                <?php echo ui_text(strtoupper(substr((string) ($p['flavor'] ?? 'idp'), 0, 1))); ?>
              </span>
              <span class="grow text-left"><?php echo ui_text(__('ui.auth.sign_in_with', ['name' => (string) $p['name']], null, 'Sign in with {name}')); ?></span>
              <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M7 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
          </li>
        <?php } ?>
      </ul>

      <div class="mb-6 flex items-center gap-3 text-theme-xs uppercase text-gray-400">
        <span class="h-px grow bg-gray-200 dark:bg-gray-800"></span>
        <span><?php echo ui_text(__('ui.auth.or_local', [], null, 'or')); ?></span>
        <span class="h-px grow bg-gray-200 dark:bg-gray-800"></span>
      </div>
    <?php } ?>

    <form method="post" action="<?php echo ui_attr('./'.$v->restoredPath); ?>">
      <?php ui('formField', [
        'name'         => 'id',
        'label'        => __('ui.auth.field.id', [], null, 'Login ID'),
        'autocomplete' => 'username',
        'required'     => true,
      ]); ?>
      <?php ui('formField', [
        'name'         => 'password',
        'label'        => __('ui.auth.field.password', [], null, 'Password'),
        'type'         => 'password',
        'autocomplete' => 'current-password',
        'required'     => true,
      ]); ?>

      <?php ui('button', [
        'label'      => __('ui.auth.submit', [], null, 'Sign in'),
        'type'       => 'submit',
        'variant'    => 'primary',
        'extraClass' => 'w-full',
      ]); ?>
    </form>
  <?php
      },
    ]);
  ?>
</div>

<?php }; ?>
