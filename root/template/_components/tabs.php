<?php
/*
 * Tabs partial. Args:
 *   - active:  string (tab key)
 *   - tabs:    list<array{key: string, label: string, href: string, badge?: string}>
 */
$active = $active ?? '';
$tabs   = $tabs ?? [];
?>
<nav class="ta-tabs mb-6" role="tablist" aria-label="<?php echo ui_attr(__('ui.a11y.tabs', [], null, 'Tabs')); ?>">
  <?php foreach ($tabs as $tab): ?>
    <a href="<?php echo ui_attr($tab['href']); ?>"
       role="tab"
       aria-selected="<?php echo $tab['key'] === $active ? 'true' : 'false'; ?>"
       class="ta-tab <?php echo $tab['key'] === $active ? 'ta-tab-active' : ''; ?>">
      <?php echo ui_text($tab['label']); ?>
      <?php if (!empty($tab['badge'])): ?>
        <span class="ta-badge ta-badge-gray text-[10px]"><?php echo ui_text($tab['badge']); ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
