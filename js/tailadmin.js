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

  function registerComponents() {
    const Alpine = window.Alpine;
    if (!Alpine || Alpine.__saso_registered__) return;
    Alpine.__saso_registered__ = true;

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
  }

  // Alpine v3 fires `alpine:init` once, before walking the DOM. When tailadmin.js
  // is loaded via `defer` after alpine.min.js, that event has typically already
  // fired by the time this listener runs, so the data factories never register.
  // Cover both orderings: register on the event when it fires, and also try
  // immediately in case Alpine is already started.
  document.addEventListener('alpine:init', registerComponents);
  if (window.Alpine) {
    registerComponents();
  }
})();
