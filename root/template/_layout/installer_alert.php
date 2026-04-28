<?php
/*
 * Installer warning, shown only when `installer/installer.json` is present.
 * Ported from the legacy Bootstrap alert in `root/template/root.php`.
 */
if (!file_exists('installer/installer.json')) {
    return;
}
?>
<div class="ta-alert ta-alert-warning mb-4" role="alert">
  <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
  <p>
    <?php echo ui_text(__('ui.installer.unfinished', [], null, 'Installation has not been completed yet — open ')); ?>
    <a class="underline font-medium" href="./installer/start"><?php echo ui_text(__('ui.installer.link', [], null, 'installer/start')); ?></a>
    <?php echo ui_text(__('ui.installer.unfinished_tail', [], null, '. If installation is already done, delete the "installer" directory.')); ?>
  </p>
</div>
