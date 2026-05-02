<?php $this->title = __('ui.not_found.title', [], null, 'Not found'); ?>
<?php $this->content = function ($v) { ?>

<div class="mx-auto max-w-md text-center">
  <p class="mb-4 text-6xl font-bold text-brand-500">404</p>
  <h1 class="mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90">
    <?php echo ui_text(__('ui.not_found.title', [], null, 'Not found')); ?>
  </h1>
  <p class="mb-6 text-theme-sm text-gray-500 dark:text-gray-400">
    <?php echo ui_text(__('ui.not_found.body', [], null, 'The page you are looking for does not exist.')); ?>
  </p>
  <?php ui('button', [
    'label'   => __('ui.nav.home', [], null, 'Home'),
    'type'    => 'link',
    'href'    => './',
    'variant' => 'primary',
  ]); ?>
</div>

<?php }; ?>
