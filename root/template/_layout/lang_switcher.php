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
          class="flex h-10 items-center gap-2 rounded-lg border border-gray-200 px-3 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-white/[0.05]"
          :aria-expanded="open ? 'true' : 'false'"
          aria-label="<?php echo ui_attr(__('ui.a11y.lang_switcher', [], null, 'Change language')); ?>">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
      <path d="M3 12h18M12 3a14.5 14.5 0 0 1 0 18M12 3a14.5 14.5 0 0 0 0 18" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    <span class="hidden text-theme-xs uppercase text-gray-700 sm:inline dark:text-gray-200">
      <?php echo ui_text($currentLocale); ?>
    </span>
  </button>

  <ul x-show="open" x-cloak
      class="absolute right-0 mt-2 w-40 rounded-xl border border-gray-200 bg-white py-1 shadow-theme-md dark:border-gray-800 dark:bg-gray-dark">
    <?php foreach ($supportedLocales as $lc): ?>
      <li>
        <form method="POST" action="./locale/set/<?php echo ui_attr($lc); ?>" class="block">
          <input type="hidden" name="return" value="<?php echo ui_attr($returnTo); ?>">
          <button type="submit"
                  class="flex w-full items-center justify-between px-3 py-2 text-theme-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.05] <?php echo $lc === $currentLocale ? 'font-semibold text-brand-600 dark:text-brand-400' : ''; ?>">
            <span><?php echo ui_text($labels[$lc] ?? $lc); ?></span>
            <?php if ($lc === $currentLocale): ?>
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m4 10 4 4 8-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            <?php endif; ?>
          </button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
