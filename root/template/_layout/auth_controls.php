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
 * Resilience notes:
 *   - tailadmin.js runs `applyTheme(readPersistedTheme())` synchronously
 *     when its script tag loads, so the `<html>` element gains/loses the
 *     `dark` class BEFORE Alpine boots. We therefore drive the icon swap
 *     with Tailwind `dark:` variants, not Alpine `x-show`. That way the
 *     button always shows a meaningful icon even if Alpine fails to init.
 *   - The theme toggle uses an inline `onclick` that performs the swap
 *     directly. Alpine's `@click="toggle()"` is intentionally NOT used —
 *     having both would double-toggle on click.
 */
?>
<div class="fixed top-4 right-4 z-50 flex items-center gap-2"
     role="toolbar"
     aria-label="<?php echo ui_attr(__('ui.a11y.header_controls', [], null, 'Header controls')); ?>">

  <button type="button"
          onclick="(function(){var h=document.documentElement,d=h.classList.contains('dark')?'light':'dark';if(d==='dark'){h.classList.add('dark');}else{h.classList.remove('dark');}try{localStorage.setItem('saso.theme',d);}catch(e){}})()"
          class="saso-header-btn saso-icon-btn"
          aria-label="<?php echo ui_attr(__('ui.a11y.toggle_theme', [], null, 'Toggle theme')); ?>"
          title="<?php echo ui_attr(__('ui.a11y.toggle_theme', [], null, 'Toggle theme')); ?>">
    <!-- Moon: shown in light mode (means "switch to dark") -->
    <svg class="h-5 w-5 block dark:hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <!-- Sun: shown in dark mode (means "switch to light") -->
    <svg class="h-5 w-5 hidden dark:block" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
      <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
  </button>

  <?php require __DIR__ . '/lang_switcher.php'; ?>
</div>
