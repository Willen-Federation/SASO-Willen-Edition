<?php
/*
 * Language switcher dropdown. Receives:
 *   - $currentLocale: string
 *   - $supportedLocales: list<string>
 *
 * Selection POSTs to ./locale/set/{lc} which writes the saso_locale cookie
 * and 303-redirects back via Referer (or the page's own URL as fallback).
 */
$labels = [
    'en' => 'English',
    'ja' => '日本語',
];
$returnTo = $_SERVER['REQUEST_URI'] ?? './';
?>
<div class="relative" x-data="taLang()" @click.outside="close()">
  <button type="button"
          @click="toggle()"
          class="saso-header-btn flex h-10 items-center gap-2 px-3"
          :aria-expanded="open ? 'true' : 'false'"
          aria-label="<?php echo ui_attr(__('ui.a11y.lang_switcher', [], null, 'Change language')); ?>">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
      <path d="M3 12h18M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    <span class="hidden text-xs uppercase sm:inline" style="color:var(--saso-ctrl-text)">
      <?php echo ui_text($currentLocale); ?>
    </span>
  </button>

  <!-- Hidden via `x-cloak` until Alpine binds `x-show`; the global
       `[x-cloak] { display: none !important }` rule in `css/input.css`
       guarantees the dropdown stays hidden during the brief pre-init
       window even on slow connections. -->
  <ul x-show="open" x-cloak
      class="absolute right-0 mt-2 w-44 rounded-xl border py-1"
      style="background:var(--saso-card);
             border-color:var(--saso-card-bdr);
             box-shadow:0 8px 24px rgba(0,0,0,0.22),0 2px 6px rgba(0,0,0,0.14)">
    <?php foreach ($supportedLocales as $lc): ?>
      <li>
        <form method="POST" action="./locale/set/<?php echo ui_attr($lc); ?>" class="block">
          <input type="hidden" name="return" value="<?php echo ui_attr($returnTo); ?>">
          <button type="submit"
                  class="flex w-full items-center justify-between px-3 py-2 text-sm rounded-lg mx-auto"
                  style="width:calc(100% - 8px);margin:0 4px;
                         color:<?php echo $lc === $currentLocale ? '#3c50e0' : 'var(--saso-text)'; ?>;
                         font-weight:<?php echo $lc === $currentLocale ? '600' : '400'; ?>"
                  onmouseover="this.style.background='var(--saso-ctrl-hover)'"
                  onmouseout="this.style.background='transparent'">
            <span><?php echo ui_text($labels[$lc] ?? $lc); ?></span>
            <?php if ($lc === $currentLocale): ?>
              <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m4 10 4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            <?php endif; ?>
          </button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
