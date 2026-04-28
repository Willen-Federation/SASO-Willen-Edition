<?php $this->title = __('ui.password.title', [], null, 'Change password'); ?>
<?php $this->content = function ($v) { ?>

<div class="mx-auto max-w-md">
  <?php
    ui('card', [
      'body' => function () use ($v) {
  ?>

    <h2 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">
      <?php echo ui_text(__('ui.password.title', [], null, 'Change password')); ?>
    </h2>
    <p class="mb-4 text-theme-sm text-gray-500 dark:text-gray-400">
      <?php echo ui_text(__('ui.password.subtitle', [], null, 'Save the new password somewhere safe — it cannot be recovered if lost.')); ?>
    </p>

    <?php if ($v->changed) { ?>
      <?php ui('alert', [
        'variant' => 'success',
        'body'    => __('ui.password.changed', [], null, 'Password updated successfully.'),
      ]); ?>
    <?php } ?>
    <?php if ($v->errorNow) { ?>
      <?php ui('alert', [
        'variant' => 'danger',
        'body'    => __('ui.password.wrong_current', [], null, 'The current password is not correct.'),
      ]); ?>
    <?php } ?>

    <form method="post" action="./start/password/" class="mt-4">
      <?php ui('formField', [
        'name'         => 'now',
        'id'           => 'nowPassword',
        'label'        => __('ui.password.field.now', [], null, 'Current password'),
        'type'         => 'password',
        'required'     => true,
        'autocomplete' => 'current-password',
      ]); ?>
      <?php ui('formField', [
        'name'         => 'new',
        'id'           => 'newPassword',
        'label'        => __('ui.password.field.new', [], null, 'New password'),
        'type'         => 'password',
        'required'     => true,
        'autocomplete' => 'new-password',
        'help'         => __('ui.password.help', [], null, 'Half-width letters and digits, 8–20 chars.'),
      ]); ?>
      <?php ui('formField', [
        'name'         => 'confirm',
        'id'           => 'confirmPassword',
        'label'        => __('ui.password.field.confirm', [], null, 'Confirm new password'),
        'type'         => 'password',
        'required'     => true,
        'autocomplete' => 'new-password',
      ]); ?>

      <p id="confirmPasswordError" class="hidden form-error">
        <?php echo ui_text(__('ui.password.mismatch', [], null, 'Passwords do not match.')); ?>
      </p>

      <div class="flex justify-end">
        <?php ui('button', [
          'id'       => 'changePasswordSubmit',
          'label'    => __('ui.password.submit', [], null, 'Change password'),
          'type'     => 'submit',
          'variant'  => 'primary',
          'disabled' => true,
        ]); ?>
      </div>
    </form>

  <?php
      },
    ]);
  ?>
</div>

<?php }; ?>
