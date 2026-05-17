<?php
/*
 * Sidebar partial. Receives:
 *   - $sidebar: list<array{type, label, items}> — group structure built in RootView.
 *   - $authed:  bool — when false, sidebar is hidden (only the auth screen renders).
 *   - $version: string — shown beneath the brand for support reference.
 */

if (!$authed) {
    return;
}
?>
<aside id="sidebar"
       class="saso-sidebar sidebar fixed left-0 top-0 z-99999 flex h-screen w-[290px] flex-col overflow-y-auto px-5 transition-transform duration-200 ease-linear lg:static lg:translate-x-0"
       :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       aria-label="<?php echo ui_attr(__('ui.a11y.sidebar', [], null, 'Primary')); ?>">

  <div class="flex items-center gap-2 pb-7 pt-8">
    <a href="./" class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 text-white font-bold">S</span>
      <span class="text-lg font-semibold text-gray-800 dark:text-white/90">SASO<?php echo ui_text((string) $version); ?></span>
    </a>
  </div>

  <nav class="flex flex-1 min-h-0 flex-col overflow-y-auto no-scrollbar"
       aria-label="<?php echo ui_attr(__('ui.a11y.main_nav', [], null, 'Main navigation')); ?>"
       x-data="{ selected: $persist(null).as('saso.sidebar.selected') }">
    <?php
      // TailAdmin sidebar utility class strings (uses pre-compiled tailadmin.css).
      $menuGroupTitle = 'mb-3 px-2 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400';
      $menuItemBase   = 'flex items-start gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors';
      $menuItemActive = 'bg-brand-50 text-brand-600 dark:bg-white/[0.05] dark:text-white';
      $menuItemIdle   = 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.05] dark:hover:text-white';
    ?>
    <?php foreach ($sidebar as $group): ?>
      <div class="mb-4">
        <h3 class="<?php echo $menuGroupTitle; ?>">
          <?php echo ui_text($group['label']); ?>
        </h3>
        <ul class="flex flex-col gap-1.5">
          <?php foreach ($group['items'] as $item): ?>
            <?php $isActive = isset($activeKey) && $activeKey === ($item['key'] ?? ''); ?>
            <?php if (empty($item['children'])): ?>
              <li>
                <a href="<?php echo ui_attr($item['href']); ?>"
                   class="<?php echo $menuItemBase . ' ' . ($isActive ? $menuItemActive : $menuItemIdle); ?>"
                   <?php if ($isActive): ?>aria-current="page"<?php endif; ?>>
                  <span class="mt-0.5"><?php echo $item['icon'] ?? ''; ?></span>
                  <span class="min-w-0 grow">
                    <span class="block leading-5"><?php echo ui_text($item['label']); ?></span>
                    <?php if (!empty($item['description'])): ?>
                      <span class="mt-0.5 block text-xs font-normal leading-4 text-gray-500 dark:text-gray-400"><?php echo ui_text($item['description']); ?></span>
                    <?php endif; ?>
                  </span>
                  <?php if (!empty($item['new'])): ?>
                    <span class="ta-badge ta-badge-primary mt-0.5 text-[10px]">NEW</span>
                  <?php endif; ?>
                </a>
              </li>
            <?php else: ?>
              <li x-data="{ groupKey: '<?php echo ui_attr($item['key'] ?? $item['label']); ?>' }">
                <button type="button"
                        @click="selected = (selected === groupKey ? null : groupKey)"
                        class="<?php echo $menuItemBase . ' ' . $menuItemIdle; ?> w-full text-left"
                        :aria-expanded="selected === groupKey ? 'true' : 'false'">
                  <span class="mt-0.5"><?php echo $item['icon'] ?? ''; ?></span>
                  <span class="min-w-0 grow">
                    <span class="block leading-5"><?php echo ui_text($item['label']); ?></span>
                    <?php if (!empty($item['description'])): ?>
                      <span class="mt-0.5 block text-xs font-normal leading-4 text-gray-500 dark:text-gray-400"><?php echo ui_text($item['description']); ?></span>
                    <?php endif; ?>
                  </span>
                  <svg class="h-4 w-4 shrink-0 transition-transform"
                       :class="selected === groupKey ? 'rotate-180' : ''"
                       viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M4.79 7.4 10 12.6l5.21-5.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                <ul x-show="selected === groupKey" class="ml-7 mt-1 flex flex-col gap-1 border-l border-gray-200 pl-4 dark:border-gray-700">
                  <?php foreach ($item['children'] as $child): ?>
                    <li>
                      <a href="<?php echo ui_attr($child['href']); ?>"
                         class="block rounded-md px-2 py-1.5 text-theme-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/[0.05] dark:hover:text-white">
                        <span class="block leading-5"><?php echo ui_text($child['label']); ?></span>
                        <?php if (!empty($child['description'])): ?>
                          <span class="mt-0.5 block text-xs leading-4 text-gray-500 dark:text-gray-500"><?php echo ui_text($child['description']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($child['new'])): ?>
                          <span class="ta-badge ta-badge-primary text-[10px] ml-1">NEW</span>
                        <?php endif; ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </nav>
</aside>

<!-- Mobile sidebar overlay -->
<div x-show="mobileOpen"
     x-cloak
     @click="mobileOpen = false"
     @keydown.escape.window="mobileOpen = false"
     class="ta-sidebar-overlay"
     aria-hidden="true"></div>
