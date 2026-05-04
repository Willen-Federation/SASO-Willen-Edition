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
$switcherId = 'lang-switcher-' . substr(bin2hex(random_bytes(4)), 0, 6);
?>
<div class="dropdown">
  <button type="button"
          class="btn btn-sm btn-outline-secondary dropdown-toggle"
          id="<?php echo ui_attr($switcherId); ?>"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label="<?php echo ui_attr(__('ui.a11y.lang_switcher', [], null, 'Change language')); ?>">
    <i class="ti ti-world me-1"></i>
    <span class="text-uppercase"><?php echo ui_text($currentLocale); ?></span>
  </button>
  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="<?php echo ui_attr($switcherId); ?>">
    <?php foreach ($supportedLocales as $lc): ?>
      <li>
        <form method="POST" action="./locale/set/<?php echo ui_attr($lc); ?>" class="m-0">
          <input type="hidden" name="return" value="<?php echo ui_attr($returnTo); ?>">
          <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between<?php echo $lc === $currentLocale ? ' active' : ''; ?>">
            <span><?php echo ui_text($labels[$lc] ?? $lc); ?></span>
            <?php if ($lc === $currentLocale): ?>
              <i class="ti ti-check ms-2"></i>
            <?php endif; ?>
          </button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
