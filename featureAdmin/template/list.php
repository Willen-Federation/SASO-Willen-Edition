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
            'href'    => '/admin/feature-flags/',
            'variant' => 'secondary',
          ]);
      },
      'body' => function () use ($v) {
        $rows = [];
        foreach ($v->flags as $f) {
            $isTripped = $f['autoDisabledAt'] !== null;
            $statusBadge = $isTripped
                ? '<span class="ta-badge ta-badge-danger">'.ui_text(__('ui.feature_flags.status.tripped', [], null, 'Breaker tripped')).'</span>'
                : ($f['enabled']
                    ? '<span class="ta-badge ta-badge-success">'.ui_text(__('ui.feature_flags.status.active', [], null, 'Active')).'</span>'
                    : '<span class="ta-badge ta-badge-gray">'.ui_text(__('ui.feature_flags.status.disabled', [], null, 'Disabled')).'</span>');
            $rolloutBadge = '<span class="ta-badge ta-badge-primary">'.((int) $f['rolloutPercent']).'%</span>';
            
            $toggleAction = $f['enabled'] ? 'disable' : 'enable';
            $toggleLabel = $f['enabled'] ? 'Disable' : 'Enable';
            $toggleVariant = $f['enabled'] ? 'danger' : 'success';
            
            $csrf = htmlspecialchars(\saso\util\CSRFtoken::current());
            $keyHtml = htmlspecialchars($f['key']);
            
            $actionForm = <<<HTML
<form method="POST" action="" class="inline-block">
    <input type="hidden" name="csrftoken" value="{$csrf}">
    <input type="hidden" name="flag_key" value="{$keyHtml}">
    <input type="hidden" name="action" value="{$toggleAction}">
    <button type="submit" class="ta-btn ta-btn-sm ta-btn-{$toggleVariant}">{$toggleLabel}</button>
</form>
HTML;

            $rows[] = [
                ['value' => '<code class="text-theme-xs">'.ui_text((string) $f['key']).'</code>', 'html' => true],
                ['value' => ui_text((string) $f['description'])],
                ['value' => $rolloutBadge,  'html' => true],
                ['value' => $statusBadge,   'html' => true],
                ['value' => $actionForm,    'html' => true],
            ];
        }
        ui('table', [
            'columns' => [
                ['label' => __('ui.feature_flags.col.key',         [], null, 'Key')],
                ['label' => __('ui.feature_flags.col.description', [], null, 'Description')],
                ['label' => __('ui.feature_flags.col.rollout',     [], null, 'Rollout')],
                ['label' => __('ui.feature_flags.col.status',      [], null, 'Status')],
                ['label' => __('ui.feature_flags.col.actions',     [], null, 'Actions')],
            ],
            'rows'    => $rows,
            'caption' => __('ui.feature_flags.table_caption', [], null, 'Configured feature flags'),
            'empty'   => __('ui.feature_flags.empty', [], null, 'No flags registered. Run the M4 feature_flag migration and seed via the REST API.'),
        ]);
      },
    ]);
  ?>

<?php } ?>

<?php }; ?>
