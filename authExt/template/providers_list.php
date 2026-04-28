<?php $this->content = function ($v) { ?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.auth_providers.forbidden_title', [], null, 'Admin access required'),
    'body'    => __('ui.auth_providers.forbidden_body', [], null, 'Sign in as a user with role=admin to manage authentication providers.'),
  ]); ?>
<?php } else { ?>

  <?php
    ui('card', [
      'title' => __('ui.auth_providers.title', [], null, 'Authentication providers'),
      'actions' => function () {
          ui('button', [
              'label'   => __('ui.auth_providers.add', [], null, 'Add provider'),
              'type'    => 'link',
              'href'    => './auth/provider/new',
              'variant' => 'primary',
          ]);
      },
      'body' => function () use ($v) {
          $rows = [];
          foreach ($v->providers as $p) {
              $statusBadge = $p['enabled']
                  ? '<span class="ta-badge ta-badge-success">'.ui_text(__('ui.auth_providers.status.active', [], null, 'Active')).'</span>'
                  : '<span class="ta-badge ta-badge-gray">'.ui_text(__('ui.auth_providers.status.disabled', [], null, 'Disabled')).'</span>';
              $defaultMark = $p['is_default']
                  ? '<span class="ml-1 text-warning-500" aria-label="default">★</span>'
                  : '';
              $rows[] = [
                  ['value' => '<span class="ta-badge ta-badge-primary uppercase">'.ui_text($p['flavor']).'</span>', 'html' => true],
                  ['value' => ui_text($p['name']).$defaultMark, 'html' => true],
                  ['value' => ui_text((string) ($p['issuer'] ?? '')) ],
                  ['value' => $statusBadge, 'html' => true],
              ];
          }
          ui('table', [
              'columns' => [
                  ['label' => __('ui.auth_providers.col.flavor', [], null, 'Type')],
                  ['label' => __('ui.auth_providers.col.name',   [], null, 'Name')],
                  ['label' => __('ui.auth_providers.col.issuer', [], null, 'Issuer / Metadata')],
                  ['label' => __('ui.auth_providers.col.status', [], null, 'Status')],
              ],
              'rows'    => $rows,
              'caption' => __('ui.auth_providers.table_caption', [], null, 'Configured authentication providers'),
              'empty'   => __('ui.auth_providers.empty', [], null, 'No providers configured. Add Auth0, Cognito, Firebase, generic OIDC, or SAML.'),
          ]);
      },
    ]);
  ?>

  <div class="mt-6">
    <?php ui('alert', [
      'variant' => 'info',
      'title'   => __('ui.auth_providers.help.title', [], null, 'About this screen'),
      'body'    => __('ui.auth_providers.help.body', [], null, 'Provider management is read-only in this release. Use the Phinx migration `M4/20260426120002_create_auth_provider.php` to seed rows; the create/edit form ships in a follow-up.'),
    ]); ?>
  </div>

<?php } ?>

<?php }; ?>
