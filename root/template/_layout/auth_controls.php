<?php
/*
 * Floating controls for unauthenticated pages (login etc.).
 *
 * Mirrors the theme + language switcher controls that live in the
 * authenticated `header.php`, but rendered as a minimal floating
 * toolbar so the login screen stays chromeless (no sidebar, no
 * top app bar). Receives:
 *   - $currentLocale:    string
 *   - $supportedLocales: list<string>
 *
 * Alpine data:
 *   - `taTheme()` is bound on <html> in root.php so `theme` and
 *     `toggle()` are reachable from this scope.
 *   - `lang_switcher.php` defines its own `taLang()` scope.
 */
?>
<div class="fixed top-4 right-4 z-50 flex items-center gap-2"
     role="toolbar"
     aria-label="<?php echo ui_attr(__('ui.a11y.header_controls', [], null, 'Header controls')); ?>">

  <button type="button"
          @click="toggle()"
          class="saso-header-btn saso-icon-btn"
          :aria-label="theme === 'dark'
            ? '<?php echo ui_attr(__('ui.a11y.switch_to_light', [], null, 'Switch to light mode')); ?>'
            : '<?php echo ui_attr(__('ui.a11y.switch_to_dark',  [], null, 'Switch to dark mode')); ?>'">
    <svg x-show="theme === 'light'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
      <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
  </button>

  <?php require __DIR__ . '/lang_switcher.php'; ?>
</div>
