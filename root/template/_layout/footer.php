<?php
/*
 * Footer partial. Receives:
 *   - $version: string
 */
$year = date('Y');
?>
<footer role="contentinfo"
        class="mt-auto border-t border-gray-200 bg-gray-50 py-4 px-6 text-center text-theme-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
  <p>
    <?php echo ui_text(__('ui.footer.copyright', ['year' => $year], null, '© {year} SASO — Willen Edition')); ?>
    &mdash;
    <?php echo ui_text(__('ui.footer.version', ['version' => $version], null, 'Version {version}')); ?>
  </p>
</footer>
