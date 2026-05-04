<?php
/*
 * Tabs partial. Args:
 *   - active:  string (tab key)
 *   - tabs:    list<array{key: string, label: string, href: string, badge?: string}>
 */
$active = $active ?? '';
$tabs   = $tabs ?? [];
?>
<ul class="nav nav-tabs mb-3" role="tablist" aria-label="<?php echo ui_attr(__('ui.a11y.tabs', [], null, 'Tabs')); ?>">
  <?php foreach ($tabs as $tab): ?>
    <li class="nav-item" role="presentation">
      <a href="<?php echo ui_attr($tab['href']); ?>"
         class="nav-link <?php echo $tab['key'] === $active ? 'active' : ''; ?>"
         aria-selected="<?php echo $tab['key'] === $active ? 'true' : 'false'; ?>">
        <?php echo ui_text($tab['label']); ?>
        <?php if (!empty($tab['badge'])): ?>
          <span class="badge bg-gray-200 text-gray-700 ms-2"><?php echo ui_text($tab['badge']); ?></span>
        <?php endif; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
