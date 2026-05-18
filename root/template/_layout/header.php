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
                :aria-expanded="mobileOpen ? 'true' : 'false'"
                aria-controls="sidebar"
                class="saso-header-btn saso-icon-btn lg:hidden"
                aria-label="<?php echo ui_attr(__('ui.a11y.toggle_sidebar', [], null, 'Toggle sidebar')); ?>">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
            <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      <?php endif; ?>
      <span class="text-base font-semibold lg:hidden" style="color:var(--saso-text)" aria-hidden="true">
        <?php echo ui_text($title); ?>
      </span>
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
                   role="searchbox"
                   class="saso-header-search block w-full py-2 pl-9 pr-3 text-sm"
                   placeholder="<?php echo ui_attr(__('ui.header.search_placeholder', [], null, 'Search items...')); ?>"
                   aria-label="<?php echo ui_attr(__('ui.a11y.search', [], null, 'Search')); ?>"
                   onkeypress="if(event.key==='Enter'){location.href='/search/start/search/'+encodeURI(this.value.replace(/\//g,''))}">
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Right: theme, lang, user ── -->
    <div class="flex items-center gap-2" role="toolbar" aria-label="<?php echo ui_attr(__('ui.a11y.header_controls', [], null, 'Header controls')); ?>">

      <!-- Theme toggle — inline onclick + Tailwind `dark:` icon swap means
           this stays functional even if Alpine fails to initialize. -->
      <button type="button"
              onclick="(function(){var h=document.documentElement,d=h.classList.contains('dark')?'light':'dark';if(d==='dark'){h.classList.add('dark');}else{h.classList.remove('dark');}try{localStorage.setItem('saso.theme',d);}catch(e){}})()"
              class="saso-header-btn saso-icon-btn"
              aria-label="<?php echo ui_attr(__('ui.a11y.toggle_theme', [], null, 'Toggle theme')); ?>"
              title="<?php echo ui_attr(__('ui.a11y.toggle_theme', [], null, 'Toggle theme')); ?>">
        <svg class="h-5 w-5 block dark:hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg class="h-5 w-5 hidden dark:block" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
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
                  :aria-expanded="open ? 'true' : 'false'"
                  aria-haspopup="true"
                  aria-label="<?php echo ui_attr(__('ui.a11y.user_menu', [], null, 'User menu')); ?>">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                  style="background:rgba(60,80,224,0.12);color:#3c50e0"
                  aria-hidden="true">
              <?php echo ui_text(mb_substr((string)($userName ?? '?'), 0, 1)); ?>
            </span>
            <span class="hidden text-sm sm:inline" style="color:var(--saso-ctrl-text)">
              <?php echo ui_text((string)$userName); ?>
            </span>
          </button>

          <!-- Dropdown menu — `x-cloak` + the global `[x-cloak]` rule in
               `css/input.css` keeps it hidden until Alpine binds `x-show`. -->
          <ul x-show="open" x-cloak
              role="menu"
              class="absolute right-0 mt-2 w-48 rounded-xl py-1"
              style="background:var(--saso-card);
                     border:1.5px solid var(--saso-card-bdr);
                     box-shadow:0 8px 24px rgba(0,0,0,0.22),0 2px 6px rgba(0,0,0,0.14)">
            <li role="none">
              <a href="/mypage/"
                 role="menuitem"
                 class="block rounded-lg text-sm px-3 py-2 mx-1 transition-colors"
                 style="color:var(--saso-text)"
                 onmouseover="this.style.background='var(--saso-ctrl-hover)'"
                 onmouseout="this.style.background='transparent'">
                <?php echo ui_text(__('ui.user_menu.my_page', [], null, 'My Page')); ?>
              </a>
            </li>
            <li role="none">
              <a href="/start/password/"
                 role="menuitem"
                 class="block rounded-lg text-sm px-3 py-2 mx-1 transition-colors"
                 style="color:var(--saso-text)"
                 onmouseover="this.style.background='var(--saso-ctrl-hover)'"
                 onmouseout="this.style.background='transparent'">
                <?php echo ui_text(__('ui.user_menu.change_password', [], null, 'Change password')); ?>
              </a>
            </li>
            <li role="none">
              <a href="/start/logout/"
                 role="menuitem"
                 class="block rounded-lg text-sm px-3 py-2 mx-1 transition-colors"
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
