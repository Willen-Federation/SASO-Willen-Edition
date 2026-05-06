<?php
/*
 * Skip-link partial. Should be the first child of <body> so the link is the
 * first focusable element when a keyboard user starts tabbing.
 */
?>
<a class="sr-only focus:not-sr-only fixed left-2 top-2 z-99999 rounded-lg bg-brand-500 px-4 py-2 text-white shadow-theme-md"
   href="#main-content">
  <?php echo ui_text(__('ui.a11y.skip_to_main', [], null, 'Skip to main content')); ?>
</a>
