<?php
/*
 * Footer partial. Receives:
 *   - $version: string
 */
$year = date('Y');
?>
<footer role="contentinfo"
        class="saso-footer mt-auto py-4 px-6 text-center text-sm" style="color:var(--saso-text-sub)"
  <p>
    <?php echo ui_text(__('ui.footer.copyright', ['year' => $year], null, '© {year} SASO — Willen Edition')); ?>
    &mdash;
    <?php echo ui_text(__('ui.footer.version', ['version' => $version], null, 'Version {version}')); ?>
  </p>
</footer>
