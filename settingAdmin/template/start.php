<?php $this->content = function ($v) { ?>

<?php
  $lang = $_SESSION['lang'] ?? 'ja';
  $title = __('ui.settings.title', [], null, 'System Settings');
?>

<?php if (!$v->authorized) { ?>
  <?php ui('alert', [
    'variant' => 'danger',
    'title'   => __('ui.settings.forbidden_title', [], null, '管理者権限が必要です'),
    'body'    => __('ui.settings.forbidden_body', [], null, '設定を管理するには role=admin のユーザーでサインインしてください。'),
  ]); ?>
<?php } else { ?>

  <?php
    ui('card', [
      'title'   => $title,
      'body'    => function () use ($v, $lang) {
  ?>
    <form method="POST" action="">
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current(), ENT_QUOTES, 'UTF-8'); ?>">

      <?php if (!empty($v->message)): ?>
        <div class="mb-4">
          <?php ui('alert', ['variant' => 'success', 'body' => $v->message]); ?>
        </div>
      <?php endif; ?>

      <?php if ($v->envOverrides['APP_HTTPS']): ?>
        <div class="alert alert-warning mb-4">
          This setting is currently overridden by <code>.env</code> (APP_HTTPS). UI edits will not take effect until the <code>.env</code> entry is removed.
        </div>
      <?php endif; ?>

      <div class="mb-4 border-top pt-4">
        <h4 class="mb-3 small fw-semibold text-uppercase text-muted">
          <?php echo $lang === 'ja' ? '一般設定' : 'General Settings'; ?>
        </h4>
      </div>

      <?php
      ui('formField', [
        'name'        => 'default_locale',
        'label'       => 'Default Locale',
        'type'        => 'select',
        'value'       => $v->settings['default_locale'] ?? 'en',
        'options'     => [
          'en' => 'English',
          'ja' => '日本語',
        ],
        'help'        => 'System default language for anonymous users.',
      ]);
      ?>

      <div class="mb-4 border-top pt-4">
        <h4 class="mb-3 small fw-semibold text-uppercase text-muted">
          <?php echo $lang === 'ja' ? 'メール送信設定' : 'Mail Settings'; ?>
        </h4>
      </div>

      <?php
      ui('formField', [
        'name'        => 'mail.smtp_host',
        'label'       => 'SMTP Host',
        'value'       => $v->settings['mail.smtp_host'] ?? '',
        'placeholder' => 'smtp.example.com',
      ]);

      ui('formField', [
        'name'        => 'mail.smtp_port',
        'label'       => 'SMTP Port',
        'type'        => 'number',
        'value'       => (string)($v->settings['mail.smtp_port'] ?? '25'),
      ]);
      ?>

      <div class="mb-4 border-top pt-4">
        <h4 class="mb-3 small fw-semibold text-uppercase text-muted">
          <?php echo $lang === 'ja' ? 'ラベル印刷設定' : 'Label Printing'; ?>
        </h4>
      </div>

      <?php
      ui('formField', [
        'name'        => 'outputRow',
        'label'       => 'Output Rows (Default)',
        'type'        => 'number',
        'value'       => (string)($v->settings['outputRow'] ?? '2'),
      ]);

      ui('formField', [
        'name'        => 'sheetAmount',
        'label'       => 'Labels per Sheet (Default)',
        'type'        => 'number',
        'value'       => (string)($v->settings['sheetAmount'] ?? '10'),
      ]);
      ?>

      <div class="mb-4 border-top pt-4">
        <h4 class="mb-3 small fw-semibold text-uppercase text-muted">
          <?php echo $lang === 'ja' ? '認証設定' : 'Authentication'; ?>
        </h4>
      </div>

      <?php
      ui('formField', [
        'name'        => 'auth.mode',
        'label'       => 'Authentication Mode',
        'type'        => 'select',
        'value'       => $v->settings['auth.mode'] ?? 'local',
        'options'     => [
          'local' => 'Local Only',
          'oidc'  => 'OIDC Only',
          'saml'  => 'SAML Only',
          'all'   => 'Local + OIDC/SAML',
        ],
        'help'        => 'Master toggle for authentication strategies.',
      ]);
      ?>

      <?php
      ui('button', [
        'label'      => $lang === 'ja' ? '保存する' : 'Save Settings',
        'type'       => 'submit',
        'variant'    => 'primary',
        'extraClass' => 'w-100 mt-4',
      ]);
      ?>
    </form>
  <?php
      },
    ]);
  ?>

<?php } ?>

<?php }; ?>
