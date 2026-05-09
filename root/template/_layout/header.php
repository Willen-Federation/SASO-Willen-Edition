<?php
/*
 * Header partial. Receives:
 *   - $authed: bool
 *   - $userName: string|null
 *   - $currentLocale: string
 *   - $supportedLocales: list<string>
 *   - $title: string
 */
?>
<header role="banner" class="saso-header sticky top-0 z-9999 flex w-full">
  <div class="flex grow items-center justify-between px-4 py-3 lg:px-6">

    <!-- ── Left: hamburger + page title (mobile) ── -->
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
      <h1 class="text-base font-semibold lg:hidden" style="color:var(--saso-text)">
        <?php echo ui_text($title); ?>
      </h1>
    </div>

    <!-- ── Centre: search (desktop) ── -->
    <?php if ($authed): ?>
      <div class="hidden grow justify-center px-4 sm:flex lg:px-6">
        <div class="w-full max-w-md">
          <label for="header-search" class="sr-only">
            <?php echo ui_text(__('ui.a11y.search', [], null, 'Search')); ?>
          </label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                 style="color:var(--saso-text-sub)" aria-hidden="true">
              <?php ui('iconHeroicon', ['name' => 'search', 'class' => 'h-4 w-4']); ?>
            </div>
            <input id="header-search"
                   type="search"
                   class="saso-header-search block w-full py-2 pl-9 pr-3 text-sm"
                   placeholder="<?php echo ui_attr(__('ui.header.search_placeholder', [], null, 'Search items...')); ?>"
                   onkeypress="if(event.key==='Enter'){location.href='./start/start/search/'+encodeURI(this.value.replace(/\//g,''))}">
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Right: theme, lang, user ── -->
    <div class="flex items-center gap-2">

      <!-- Theme toggle -->
      <button type="button"
              @click="toggle()"
              class="saso-header-btn h-10 w-10"
              :aria-label="theme === 'dark'
                ? '<?php echo ui_attr(__('ui.a11y.switch_to_light', [], null, 'Switch to light mode')); ?>'
                : '<?php echo ui_attr(__('ui.a11y.switch_to_dark',  [], null, 'Switch to dark mode')); ?>'">
        <svg x-show="theme === 'light'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
          <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
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
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full
                         bg-primary/10 text-primary text-sm font-semibold
                         dark:bg-white/10 dark:text-white">
              <?php echo ui_text(mb_substr((string)($userName ?? '?'), 0, 1)); ?>
            </span>
            <span class="hidden text-sm sm:inline" style="color:var(--saso-ctrl-text)">
              <?php echo ui_text((string)$userName); ?>
            </span>
          </button>

          <!-- Dropdown -->
          <ul x-show="open" x-cloak
              class="absolute right-0 mt-2 w-48 rounded-xl border py-1"
              style="background:var(--saso-card);
                     border-color:var(--saso-card-bdr);
                     box-shadow:0 8px 24px rgba(0,0,0,0.22),0 2px 6px rgba(0,0,0,0.14)"
            <li>
              <a href="/start/password/"
                 class="block rounded-lg text-sm px-3 py-2 mx-1"
                 style="color:var(--saso-text)"
                 onmouseover="this.style.background='var(--saso-ctrl-hover)'"
                 onmouseout="this.style.background='transparent'">
                <?php echo ui_text(__('ui.user_menu.change_password', [], null, 'Change password')); ?>
              </a>
            </li>
            <li>
              <a href="/start/logout/"
                 class="block rounded-lg text-sm px-3 py-2 mx-1"
                 style="color:#dc2626"
                 onmouseover="this.style.background='rgba(220,38,38,0.08)'"
                 onmouseout="this.style.background='transparent'">
                <?php echo ui_text(__('ui.user_menu.logout', [], null, 'Sign out')); ?>
              </a>
            </li>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>
