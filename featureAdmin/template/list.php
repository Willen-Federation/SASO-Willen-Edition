<?php $this->content = function ($v) { ?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.feature_flags.forbidden_title', [], null, 'Admin access required'),
    'body'    => __('ui.feature_flags.forbidden_body', [], null, 'Sign in as a user with role=admin to manage feature flags.'),
  ]); ?>
<?php } else { ?>

  <?php
    ui('card', [
      'title' => __('ui.feature_flags.title', [], null, 'Feature flags'),
      'actions' => function () {
          ui('button', [
            'label'   => __('ui.feature_flags.refresh', [], null, 'Refresh'),
            'type'    => 'link',
            'href'    => './admin/feature-flags/',
            'variant' => 'secondary',
          ]);
      },
      'body' => function () use ($v) {
        $rows = [];
        foreach ($v->flags as $f) {
            $statusBadge = $f['autoDisabledAt'] !== null
                ? '<span class="ta-badge ta-badge-danger">'.ui_text(__('ui.feature_flags.status.tripped', [], null, 'Breaker tripped')).'</span>'
                : ($f['enabled']
                    ? '<span class="ta-badge ta-badge-success">'.ui_text(__('ui.feature_flags.status.active', [], null, 'Active')).'</span>'
                    : '<span class="ta-badge ta-badge-gray">'.ui_text(__('ui.feature_flags.status.disabled', [], null, 'Disabled')).'</span>');
            $rolloutBadge = '<span class="ta-badge ta-badge-primary">'.((int) $f['rolloutPercent']).'%</span>';
            $rows[] = [
                ['value' => '<code class="text-theme-xs">'.ui_text((string) $f['key']).'</code>', 'html' => true],
                ['value' => ui_text((string) $f['description'])],
                ['value' => $rolloutBadge,  'html' => true],
                ['value' => $statusBadge,   'html' => true],
            ];
        }
        ui('table', [
            'columns' => [
                ['label' => __('ui.feature_flags.col.key',         [], null, 'Key')],
                ['label' => __('ui.feature_flags.col.description', [], null, 'Description')],
                ['label' => __('ui.feature_flags.col.rollout',     [], null, 'Rollout')],
                ['label' => __('ui.feature_flags.col.status',      [], null, 'Status')],
            ],
            'rows'    => $rows,
            'caption' => __('ui.feature_flags.table_caption', [], null, 'Configured feature flags'),
            'empty'   => __('ui.feature_flags.empty', [], null, 'No flags registered. Run the M4 feature_flag migration and seed via the REST API.'),
        ]);
      },
    ]);
  ?>

  <div class="mt-6">
    <?php ui('alert', [
      'variant' => 'info',
      'title'   => __('ui.feature_flags.help.title', [], null, 'How to update'),
      'body'    => __('ui.feature_flags.help.body', [], null, 'Toggle flags via the REST API at /api/v1/feature-flags or the MCP tools list_feature_flags / update_feature_flag. The full inline edit screen ships in a follow-up.'),
    ]); ?>
  </div>

<?php } ?>

<?php }; ?>
