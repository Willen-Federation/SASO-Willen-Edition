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
    'info'    => 'ta-alert-info',
    'success' => 'ta-alert-success',
    'warning' => 'ta-alert-warning',
    'danger'  => 'ta-alert-danger',
][$variant] ?? 'ta-alert-info';

$role = $role ?? (in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status');
?>
<div class="ta-alert <?php echo ui_attr($variantClass); ?>" role="<?php echo ui_attr($role); ?>"
     <?php if ($dismissible): ?>x-data="{shown:true}" x-show="shown"<?php endif; ?>>
  <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <?php if ($variant === 'success'): ?>
      <path d="m4 12 5 5L20 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <?php elseif ($variant === 'warning'): ?>
      <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    <?php elseif ($variant === 'danger'): ?>
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <?php else: ?>
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 8h.01M11 12h1v4h1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    <?php endif; ?>
  </svg>
  <div class="grow">
    <?php if ($title): ?>
      <p class="font-medium"><?php echo ui_text($title); ?></p>
    <?php endif; ?>
    <div>
      <?php if ($body instanceof Closure) { $body(); } else { echo ui_text((string) $body); } ?>
    </div>
  </div>
  <?php if ($dismissible): ?>
    <button type="button" @click="shown=false" class="shrink-0 text-current opacity-70 hover:opacity-100"
            aria-label="<?php echo ui_attr(__('ui.button.dismiss', [], null, 'Dismiss')); ?>">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M6 6 18 18M6 18 18 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </button>
  <?php endif; ?>
</div>
