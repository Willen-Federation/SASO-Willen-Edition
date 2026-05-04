<?php
/*
 * Tabler Icon. Args:
 *   - name:   string  (Tabler icon name, e.g. 'home', 'pencil', 'trash')
 *   - class?: string  (extra classes; default 'icon')
 *   - title?: string  (when provided, sets aria-label and adds <title>)
 *
 * Tabler Icons are rendered via webfont CSS class ti ti-{name}
 * (loaded via CDN in root.php). This partial is a compatibility shim
 * replacing the old Heroicons partial.
 */
$name  = $name ?? '';
$class = $class ?? 'icon';
$title = $title ?? null;

$tabler_name = $name;
?>
<i class="ti ti-<?php echo ui_attr($tabler_name); ?> <?php echo ui_attr($class); ?>"
   <?php if ($title): ?>role="img" aria-label="<?php echo ui_attr($title); ?>" title="<?php echo ui_attr($title); ?>"<?php else: ?>aria-hidden="true"<?php endif; ?>></i>
