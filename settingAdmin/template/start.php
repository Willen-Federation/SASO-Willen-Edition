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
      <input type="hidden" name="csrftoken" value="<?php echo htmlspecialchars(\saso\util\CSRFtoken::current()); ?>">

      <?php if (!empty($v->message)): ?>
        <div class="mb-4">
          <?php ui('alert', ['variant' => 'success', 'body' => $v->message]); ?>
        </div>
      <?php endif; ?>

      <?php if ($v->envOverrides['APP_HTTPS']): ?>
        <div class="mb-4 rounded border border-amber-200 bg-amber-50 py-3 px-4 text-amber-600 dark:border-amber-900/30 dark:bg-amber-900/10 dark:text-amber-400">
          This setting is currently overridden by <code>.env</code> (APP_HTTPS). UI edits will not take effect until the <code>.env</code> entry is removed.
        </div>
      <?php endif; ?>

      <div class="mb-4 border-t border-gray-200 pt-4 dark:border-gray-800">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
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

      <div class="mb-4 border-t border-gray-200 pt-4 dark:border-gray-800">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
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

      <div class="mb-4 border-t border-gray-200 pt-4 dark:border-gray-800">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
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

      <div class="mb-4 border-t border-gray-200 pt-4 dark:border-gray-800">
        <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
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
        'extraClass' => 'w-full justify-center mt-6',
      ]);
      ?>
    </form>

    <!-- Quick links to related settings pages -->
    <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
      <h4 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
        <?php echo $lang === 'ja' ? '関連設定' : 'Related Settings'; ?>
      </h4>
      <div class="flex flex-col gap-2">
        <a href="./settingAdmin/itemFields"
           class="flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium transition
                  hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
           style="border-color:var(--saso-card-bdr);color:var(--saso-text)">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 opacity-60" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
          </svg>
          <?php echo $lang === 'ja' ? '商品入力項目の表示設定' : 'Item Form Field Visibility'; ?>
        </a>
        <a href="./itemAttribute/start/"
           class="flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium transition
                  hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
           style="border-color:var(--saso-card-bdr);color:var(--saso-text)">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 opacity-60" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
          </svg>
          <?php echo $lang === 'ja' ? 'カスタム属性の管理' : 'Manage Custom Attributes'; ?>
        </a>
      </div>
    </div>

  <?php
      },
    ]);
  ?>

<?php } ?>

<?php }; ?>
