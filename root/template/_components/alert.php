<?php
/*
 * Alert partial. Args:
 *   - variant?: 'info'|'success'|'warning'|'danger' (default 'info')
 *   - title?:   string
 *   - body:     string|Closure (string is escaped; Closure invoked for raw HTML)
 *   - role?:    'alert'|'status' (default 'alert' for danger/warning, 'status' otherwise)
 *   - dismissible?: bool
 */
$variant = $variant ?? 'info';
$title   = $title ?? null;
$body    = $body  ?? '';
$dismissible = !empty($dismissible);

$variantClass = [
    'info'    => 'alert-info',
    'success' => 'alert-success',
    'warning' => 'alert-warning',
    'danger'  => 'alert-danger',
][$variant] ?? 'alert-info';

$role = $role ?? (in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status');
?>
<div class="alert <?php echo ui_attr($variantClass); ?>" role="<?php echo ui_attr($role); ?>"
     <?php if ($dismissible): ?>data-bs-dismiss="alert"<?php endif; ?>>
  <?php if ($dismissible): ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo ui_attr(__('ui.button.dismiss', [], null, 'Dismiss')); ?>"></button>
  <?php endif; ?>
  <?php if ($title): ?>
    <strong><?php echo ui_text($title); ?></strong>
  <?php endif; ?>
  <div>
    <?php if ($body instanceof Closure) { $body(); } else { echo ui_text((string) $body); } ?>
  </div>
</div>
