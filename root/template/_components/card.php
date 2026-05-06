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
<div class="card <?php echo ui_attr($class); ?>">
  <?php if ($title !== null || $actions !== null): ?>
    <div class="card-header">
      <?php if ($title !== null): ?>
        <h3 class="card-title">
          <?php echo ui_text($title); ?>
        </h3>
      <?php endif; ?>
      <?php if ($actions instanceof Closure): ?>
        <div class="ms-auto d-flex align-items-center gap-2"><?php $actions(); ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="card-body">
    <?php if (isset($body) && $body instanceof Closure) $body(); ?>
  </div>
</div>
