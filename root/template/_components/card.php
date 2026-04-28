<?php
/*
 * Card partial. Args:
 *   - title?:  string
 *   - body:    Closure(): void   (renders inner content)
 *   - actions?: Closure(): void  (header right-side controls)
 *   - class?:  string            (extra classes appended to .ta-card)
 */
$class = $class ?? '';
$title = $title ?? null;
$actions = $actions ?? null;
?>
<section class="ta-card <?php echo ui_attr($class); ?>">
  <?php if ($title !== null || $actions !== null): ?>
    <header class="ta-card-header">
      <?php if ($title !== null): ?>
        <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">
          <?php echo ui_text($title); ?>
        </h2>
      <?php endif; ?>
      <?php if ($actions instanceof Closure): ?>
        <div class="flex items-center gap-2"><?php $actions(); ?></div>
      <?php endif; ?>
    </header>
  <?php endif; ?>
  <?php if (isset($body) && $body instanceof Closure) $body(); ?>
</section>
