<?php
/*
 * Card partial. Args:
 *   - title?:  string
 *   - body:    Closure(): void   (renders inner content)
 *   - actions?: Closure(): void  (header right-side controls)
 *   - class?:  string            (extra classes appended)
 */
$class = $class ?? '';
$title = $title ?? null;
$actions = $actions ?? null;
?>
<section class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark <?php echo ui_attr($class); ?>">
  <?php if ($title !== null || $actions !== null): ?>
    <header class="border-b border-stroke py-4 px-6 dark:border-strokedark flex justify-between items-center">
      <?php if ($title !== null): ?>
        <h2 class="font-semibold text-black dark:text-white">
          <?php echo ui_text($title); ?>
        </h2>
      <?php endif; ?>
      <?php if ($actions instanceof Closure): ?>
        <div class="flex items-center gap-2"><?php $actions(); ?></div>
      <?php endif; ?>
    </header>
  <?php endif; ?>
  <div class="p-6">
    <?php if (isset($body) && $body instanceof Closure) $body(); ?>
  </div>
</section>
