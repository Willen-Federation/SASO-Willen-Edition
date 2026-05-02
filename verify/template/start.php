<?php $this->content = function ($v) { ?>

<div class="grid gap-6 lg:grid-cols-3">
  <div class="lg:col-span-2">
    <?php
      ui('card', [
        'title' => __('ui.verify.start_title', [], null, 'Start a verification session'),
        'body'  => function () { ?>
          <p class="mb-4 text-theme-sm text-gray-500 dark:text-gray-400">
            <?php echo ui_text(__('ui.verify.start_help', [], null, 'Pick a mode and a scope, then begin scanning. Sessions can be resumed via /verify/{id} as long as they are still active.')); ?>
          </p>

          <form method="post" action="./api/v1/verifications" class="space-y-4">
            <?php ui('formField', [
              'name'    => 'mode',
              'label'   => __('ui.verify.mode', [], null, 'Mode'),
              'type'    => 'select',
              'options' => [
                  'stocktake' => __('ui.verify.mode.stocktake', [], null, 'Stocktake (棚卸)'),
                  'spot'      => __('ui.verify.mode.spot', [], null, 'Spot verify (個別照合)'),
              ],
            ]); ?>
            <?php ui('formField', [
              'name'        => 'areaCode',
              'label'       => __('ui.verify.area', [], null, 'Area code'),
              'placeholder' => 'WH-A1',
              'help'        => __('ui.verify.area_help', [], null, 'Limit the scope to a specific shelf area. Leave blank for the whole warehouse.'),
            ]); ?>

            <div class="flex justify-end gap-2">
              <?php ui('button', [
                'label'   => __('ui.button.cancel', [], null, 'Cancel'),
                'variant' => 'secondary',
                'type'    => 'link',
                'href'    => './',
              ]); ?>
              <?php ui('button', [
                'label'   => __('ui.verify.begin', [], null, 'Begin session'),
                'variant' => 'primary',
                'type'    => 'submit',
              ]); ?>
            </div>
          </form>

          <?php ui('alert', [
            'variant' => 'info',
            'title'   => __('ui.verify.mcp_title', [], null, 'Mobile / MCP'),
            'body'    => __('ui.verify.mcp_body', [], null, 'Mobile devices can drive the entire flow via the MCP tools start_verification_session, record_verification_scan, complete_verification_session, get_verification_summary.'),
          ]); ?>
        <?php },
      ]);
    ?>
  </div>

  <div>
    <?php
      ui('card', [
        'title' => __('ui.verify.recent', [], null, 'Recent sessions'),
        'body'  => function () use ($v) {
            if (empty($v->recent)) {
                ui('alert', [
                    'variant' => 'info',
                    'body'    => __('ui.verify.no_recent', [], null, 'No verification sessions have been recorded yet.'),
                ]);
                return;
            }
            $rows = [];
            foreach ($v->recent as $s) {
                $statusBadge = $s['status'] === 'completed'
                    ? '<span class="ta-badge ta-badge-success">'.ui_text((string) $s['status']).'</span>'
                    : ($s['status'] === 'active'
                        ? '<span class="ta-badge ta-badge-warning">'.ui_text((string) $s['status']).'</span>'
                        : '<span class="ta-badge ta-badge-gray">'.ui_text((string) $s['status']).'</span>');
                $rows[] = [
                    ['value' => '#'.(int) $s['id']],
                    ['value' => ui_text((string) $s['mode']) ],
                    ['value' => ui_text((string) $s['startedAt'])],
                    ['value' => $statusBadge, 'html' => true],
                ];
            }
            ui('table', [
                'columns' => [
                    ['label' => '#'],
                    ['label' => __('ui.verify.col.mode', [], null, 'Mode')],
                    ['label' => __('ui.verify.col.started', [], null, 'Started')],
                    ['label' => __('ui.verify.col.status', [], null, 'Status')],
                ],
                'rows' => $rows,
                'caption' => __('ui.verify.recent_caption', [], null, 'Most recent verification sessions'),
            ]);
        },
      ]);
    ?>
  </div>
</div>

<?php }; ?>
