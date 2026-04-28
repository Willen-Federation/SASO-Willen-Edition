/*
 * SASO TailAdmin runtime helpers.
 *
 * Loaded ahead of `js/main.js` so the legacy module imports continue to
 * work. Provides:
 *   - `taSidebar()` — Alpine state factory for sidebar toggle / overlay
 *   - `taTheme()`   — dark-mode toggle persisted via `localStorage`
 *   - `taLang()`    — language-switcher dropdown state
 *
 * No build step. Plain ES2020 + Alpine.js v3 globals.
 */
(function () {
  'use strict';

  function readPersistedTheme() {
    try {
      const v = window.localStorage.getItem('saso.theme');
      if (v === 'dark' || v === 'light') return v;
    } catch (e) { /* private mode / disabled storage */ }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    const html = document.documentElement;
    if (theme === 'dark') html.classList.add('dark');
    else html.classList.remove('dark');
  }

  // Apply theme synchronously before paint to avoid flash-of-light-mode.
  applyTheme(readPersistedTheme());

  document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) return;

    Alpine.data('taSidebar', () => ({
      sidebarToggle: false,
      mobileOpen: false,
      toggle() { this.sidebarToggle = !this.sidebarToggle; },
      openMobile() { this.mobileOpen = true; },
      closeMobile() { this.mobileOpen = false; },
    }));

    Alpine.data('taTheme', () => ({
      theme: readPersistedTheme(),
      init() { applyTheme(this.theme); },
      toggle() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        try { window.localStorage.setItem('saso.theme', this.theme); } catch (e) {}
        applyTheme(this.theme);
      },
    }));

    Alpine.data('taLang', () => ({
      open: false,
      toggle() { this.open = !this.open; },
      close() { this.open = false; },
    }));

    Alpine.data('taDropdown', () => ({
      open: false,
      toggle() { this.open = !this.open; },
      close() { this.open = false; },
    }));

    Alpine.data('taModal', () => ({
      open: false,
      show() { this.open = true; },
      hide() { this.open = false; },
    }));
  });
})();
