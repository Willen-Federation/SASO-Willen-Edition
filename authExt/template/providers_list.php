<?php $this->content = function ($v) { ?>

<?php if ($v->forbidden) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.auth_providers.forbidden_title', [], null, 'Admin access required'),
    'body'    => __('ui.auth_providers.forbidden_body', [], null, 'Sign in as a user with role=admin to manage authentication providers.'),
  ]); ?>
<?php } ?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.auth_providers.forbidden_title', [], null, 'Admin access required'),
    'body'    => __('ui.auth_providers.forbidden_body', [], null, 'Sign in as a user with role=admin to manage authentication providers.'),
  ]); ?>
<?php } else { ?>

<?php if ($v->saved) { ?>
  <?php ui('alert', [
    'variant' => 'success',
    'title'   => __('ui.auth_providers.saved_title', [], null, 'Changes saved'),
    'body'    => __('ui.auth_providers.saved_body', [], null, 'Your changes to this provider have been saved.'),
  ]); ?>
<?php } ?>

<?php if ($v->deleted) { ?>
  <?php ui('alert', [
    'variant' => 'success',
    'title'   => __('ui.auth_providers.deleted_title', [], null, 'Provider deleted'),
    'body'    => __('ui.auth_providers.deleted_body', [], null, 'The authentication provider has been deleted.'),
  ]); ?>
<?php } ?>

  <?php
    ui('card', [
      'title' => __('ui.auth_providers.title', [], null, 'Authentication providers'),
      'actions' => function () {
          ui('button', [
              'label'   => __('ui.auth_providers.add', [], null, 'Add provider'),
              'type'    => 'link',
              'href'    => './auth/providerNew/',
              'variant' => 'primary',
          ]);
      },
      'body' => function () use ($v) {
          $rows = [];
          foreach ($v->providers as $p) {
              $statusBadge = $p['enabled']
                  ? '<span class="badge bg-success text-white">'.ui_text(__('ui.auth_providers.status.active', [], null, 'Active')).'</span>'
                  : '<span class="badge bg-secondary text-white">'.ui_text(__('ui.auth_providers.status.disabled', [], null, 'Disabled')).'</span>';
              $defaultMark = $p['is_default']
                  ? ' <span class="ms-1 text-warning" aria-label="default" title="default">★</span>'
                  : '';
              $editUrl   = './auth/provider/edit/' . $p['id'];
              $deleteUrl = './auth/provider/delete/' . $p['id'];
              $editLink = '<a href="'.ui_attr($editUrl).'" class="btn btn-sm btn-outline-primary me-2"><i class="bi bi-pencil me-1"></i>'.ui_text(__('ui.auth_providers.edit', [], null, 'Edit')).'</a>';
              $deleteLink = '<form method="post" action="'.ui_attr($deleteUrl).'" class="d-inline m-0" onsubmit="return confirm(\''.ui_attr(__('ui.auth_providers.confirm_delete', [], null, 'Delete this provider?')).'\')"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>'.ui_text(__('ui.auth_providers.delete', [], null, 'Delete')).'</button></form>';

              $rows[] = [
                  ['value' => '<span class="badge bg-primary text-white text-uppercase">'.ui_text($p['flavor']).'</span>', 'html' => true],
                  ['value' => ui_text($p['name']).$defaultMark, 'html' => true],
                  ['value' => ui_text((string) ($p['issuer'] ?? '')) ],
                  ['value' => $statusBadge, 'html' => true],
                  ['value' => $editLink . $deleteLink, 'html' => true],
              ];
          }
          ui('table', [
              'columns' => [
                  ['label' => __('ui.auth_providers.col.flavor', [], null, 'Type')],
                  ['label' => __('ui.auth_providers.col.name',   [], null, 'Name')],
                  ['label' => __('ui.auth_providers.col.issuer', [], null, 'Issuer / Metadata')],
                  ['label' => __('ui.auth_providers.col.status', [], null, 'Status')],
                  ['label' => __('ui.auth_providers.col.actions', [], null, 'Actions')],
              ],
              'rows'    => $rows,
              'caption' => __('ui.auth_providers.table_caption', [], null, 'Configured authentication providers'),
              'empty'   => __('ui.auth_providers.empty', [], null, 'No providers configured. Add Auth0, Cognito, Firebase, generic OIDC, or SAML.'),
          ]);
      },
    ]);
  ?>



<?php } ?>

<?php }; ?>
