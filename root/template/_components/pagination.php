<?php
/*
 * Pagination partial. Args:
 *   - current:  int    (1-based)
 *   - last:     int    (total pages)
 *   - urlFn:    Closure(int): string  (returns href for page N)
 *   - window?:  int    (number of pages on each side of current; default 2)
 */
$current = max(1, (int) ($current ?? 1));
$last    = max(1, (int) ($last ?? 1));
$window  = (int) ($window ?? 2);
if (!isset($urlFn) || !$urlFn instanceof Closure) {
    throw new RuntimeException('ui("pagination") requires "urlFn".');
}
$pageNum = function (int $n) use ($urlFn): string {
    return $urlFn($n);
};
$start = max(1, $current - $window);
$end   = min($last, $current + $window);
?>
<nav class="ta-pagination" aria-label="<?php echo ui_attr(__('ui.a11y.pagination', [], null, 'Pagination')); ?>">
  <span class="text-theme-sm text-gray-600 dark:text-gray-400">
    <?php echo ui_text(__('ui.pagination.summary', ['current' => $current, 'last' => $last], null, 'Page {current} / {last}')); ?>
  </span>
  <ul class="flex items-center gap-1">
    <?php if ($current > 1): ?>
      <li><a href="<?php echo ui_attr($pageNum($current - 1)); ?>" class="ta-page-link" rel="prev"><?php echo ui_text(__('ui.pagination.prev', [], null, 'Prev')); ?></a></li>
    <?php endif; ?>

    <?php if ($start > 1): ?>
      <li><a href="<?php echo ui_attr($pageNum(1)); ?>" class="ta-page-link">1</a></li>
      <?php if ($start > 2): ?>
        <li class="px-2 text-gray-400">…</li>
      <?php endif; ?>
    <?php endif; ?>

    <?php for ($p = $start; $p <= $end; $p++): ?>
      <li>
        <a href="<?php echo ui_attr($pageNum($p)); ?>"
           class="ta-page-link <?php echo $p === $current ? 'ta-page-current' : ''; ?>"
           <?php echo $p === $current ? 'aria-current="page"' : ''; ?>>
          <?php echo (int) $p; ?>
        </a>
      </li>
    <?php endfor; ?>

    <?php if ($end < $last): ?>
      <?php if ($end < $last - 1): ?>
        <li class="px-2 text-gray-400">…</li>
      <?php endif; ?>
      <li><a href="<?php echo ui_attr($pageNum($last)); ?>" class="ta-page-link"><?php echo (int) $last; ?></a></li>
    <?php endif; ?>

    <?php if ($current < $last): ?>
      <li><a href="<?php echo ui_attr($pageNum($current + 1)); ?>" class="ta-page-link" rel="next"><?php echo ui_text(__('ui.pagination.next', [], null, 'Next')); ?></a></li>
    <?php endif; ?>
  </ul>
</nav>
