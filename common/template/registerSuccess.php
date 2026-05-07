<?php $this->title = __('ui.common.success', [], null, 'Success'); ?>
<?php $this->content = function ($v) { ?>

<div class="mx-auto max-w-md text-center">
  <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-success-100 text-success-600 dark:bg-success-500/15 dark:text-success-400">
    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
  <h1 class="mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90">
    <?php echo ui_text(__('ui.common.register_success.title', [], null, 'Registered successfully')); ?>
  </h1>
  <p class="mb-6 text-theme-sm text-gray-500 dark:text-gray-400">
    <?php echo ui_text(__('ui.common.register_success.body', [], null, 'The data has been saved to the database.')); ?>
  </p>
  <?php ui('button', [
    'label'   => __('ui.nav.home', [], null, 'Home'),
    'type'    => 'link',
    'href'    => './',
    'variant' => 'primary',
  ]); ?>
</div>

<?php }; ?>
