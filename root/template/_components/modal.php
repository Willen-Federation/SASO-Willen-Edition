<?php
/*
 * Modal partial. Args:
 *   - id:       string (required) — also the DOM id; trigger via
 *               `data-bs-toggle="modal" data-bs-target="#<id>"`
 *   - title?:   string
 *   - body:     Closure(): void
 *   - footer?:  Closure(): void
 *   - size?:    'sm'|'md'|'lg'|'xl'  (default 'md')
 *
 * Usage:
 *   <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#foo">Open</button>
 *   ui('modal', ['id'=>'foo', 'title'=>__('...'), 'body'=>fn()=>...]);
 */
if (empty($id)) {
    throw new RuntimeException('ui("modal") requires "id".');
}
$title = $title ?? null;
$footer = $footer ?? null;
$size = $size ?? 'md';
$sizeClass = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
][$size] ?? '';
?>
<div class="modal modal-blur fade" id="<?php echo ui_attr($id); ?>" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered <?php echo ui_attr($sizeClass); ?>" role="document">
    <div class="modal-content">
      <?php if ($title): ?>
        <div class="modal-header">
          <h5 class="modal-title" id="<?php echo ui_attr($id); ?>-title"><?php echo ui_text($title); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo ui_attr(__('ui.button.close', [], null, 'Close')); ?>"></button>
        </div>
      <?php endif; ?>
      <div class="modal-body">
        <?php if (isset($body) && $body instanceof Closure) { $body(); } ?>
      </div>
      <?php if ($footer instanceof Closure): ?>
        <div class="modal-footer">
          <?php $footer(); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
