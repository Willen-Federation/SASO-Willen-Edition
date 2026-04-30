<?php
/*
 * Breadcrumb partial. Receives:
 *   - $title:      string (current page)
 *   - $breadcrumb: list<array{label: string, href?: string}>
 *
 * If $breadcrumb is empty, only the page title is shown.
 */
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">
    <?php echo ui_text($title); ?>
  </h1>

  <?php if (!empty($breadcrumb)): ?>
    <nav aria-label="<?php echo ui_attr(__('ui.a11y.breadcrumb', [], null, 'Breadcrumb')); ?>">
      <ol class="flex items-center gap-1.5">
        <?php foreach ($breadcrumb as $i => $crumb): ?>
          <li class="inline-flex items-center gap-1.5 text-theme-sm <?php echo $i === array_key_last($breadcrumb) ? 'text-gray-800 dark:text-white/90' : 'text-gray-500 dark:text-gray-400'; ?>">
            <?php if (!empty($crumb['href']) && $i !== array_key_last($breadcrumb)): ?>
              <a href="<?php echo ui_attr($crumb['href']); ?>" class="hover:text-brand-600 dark:hover:text-brand-400">
                <?php echo ui_text($crumb['label']); ?>
              </a>
            <?php else: ?>
              <span aria-current="page"><?php echo ui_text($crumb['label']); ?></span>
            <?php endif; ?>

            <?php if ($i !== array_key_last($breadcrumb)): ?>
              <svg class="h-4 w-4" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                <path d="M6.08 12.67 10.24 8.5 6.08 4.33" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </nav>
  <?php endif; ?>
</div>
