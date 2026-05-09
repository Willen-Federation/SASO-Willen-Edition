<?php
/*
 * Header partial. Receives:
 *   - $authed: bool
 *   - $userName: string|null
 *   - $currentLocale: string
 *   - $supportedLocales: list<string>
 *   - $title: string  (page title for mobile breadcrumb-less header)
 */
?>
<header role="banner"
        class="saso-header sticky top-0 z-9999 flex w-full border-b">
  <div class="flex grow items-center justify-between px-4 py-3 lg:px-6">
    <!-- Hamburger / sidebar toggle -->
    <div class="flex items-center gap-3">
      <?php if ($authed): ?>
        <button type="button"
                @click="mobileOpen = !mobileOpen"
                class="saso-header-btn h-10 w-10 lg:hidden"
                aria-label="<?php echo ui_attr(__('ui.a11y.toggle_sidebar', [], null, 'Toggle sidebar')); ?>">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      <?php endif; ?>
      <h1 class="text-lg font-semibold text-white lg:hidden">
        <?php echo ui_text($title); ?>
      </h1>
    </div>

    <!-- Search bar (desktop) -->
    <?php if ($authed): ?>
      <div class="hidden grow justify-center px-4 sm:flex lg:px-6">
        <div class="w-full max-w-md">
          <label for="header-search" class="sr-only"><?php echo ui_text(__('ui.a11y.search', [], null, 'Search')); ?></label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <?php ui('iconHeroicon', ['name' => 'search', 'class' => 'h-5 w-5 text-white/40']); ?>
            </div>
            <input id="header-search"
                   type="search"
                   class="saso-header-search block w-full py-2 pl-10 pr-3 text-sm"
                   placeholder="<?php echo ui_attr(__('ui.header.search_placeholder', [], null, 'Search items...')); ?>"
                   onkeypress="if(event.key === 'Enter') { location.href = './start/start/search/' + encodeURI(this.value.replace(/\//g, '')); }">
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Right controls -->
    <div class="flex items-center gap-2">
      <!-- Theme toggle -->
      <button type="button"
              @click="toggle()"
              class="saso-header-btn h-10 w-10"
              :aria-label="theme === 'dark' ? '<?php echo ui_attr(__('ui.a11y.switch_to_light', [], null, 'Switch to light mode')); ?>' : '<?php echo ui_attr(__('ui.a11y.switch_to_dark', [], null, 'Switch to dark mode')); ?>'">
        <svg x-show="theme === 'light'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>

      <!-- Language switcher -->
      <?php require __DIR__ . '/lang_switcher.php'; ?>

      <!-- User menu -->
      <?php if ($authed): ?>
        <div class="relative" x-data="taDropdown()" @click.outside="close()">
          <button type="button"
                  @click="toggle()"
                  class="saso-header-btn flex items-center gap-2 px-3 py-2"
                  :aria-expanded="open ? 'true' : 'false'">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15 text-white font-semibold text-sm">
              <?php echo ui_text(mb_substr((string) ($userName ?? '?'), 0, 1)); ?>
            </span>
            <span class="hidden text-sm text-gray-200 sm:inline">
              <?php echo ui_text((string) $userName); ?>
            </span>
          </button>
          <ul x-show="open" x-cloak
              class="absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-800 dark:bg-gray-dark">
            <li>
              <a href="/start/password/" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.05]">
                <?php echo ui_text(__('ui.user_menu.change_password', [], null, 'Change password')); ?>
              </a>
            </li>
            <li>
              <a href="/start/logout/" class="block px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">
                <?php echo ui_text(__('ui.user_menu.logout', [], null, 'Sign out')); ?>
              </a>
            </li>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>
