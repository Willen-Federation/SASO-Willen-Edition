<?php
/*
 * Modal partial. Args:
 *   - id:       string (required)
 *   - title?:   string
 *   - body:     Closure(): void
 *   - footer?:  Closure(): void
 *   - size?:    'sm'|'md'|'lg'|'xl'  (default 'md')
 *
 * Usage:
 *   <button x-data @click="$dispatch('open-modal', 'foo')">Open</button>
 *   ui('modal', ['id'=>'foo', 'title'=>__('...'), 'body'=>fn()=>...]);
 */
if (empty($id)) {
    throw new RuntimeException('ui("modal") requires "id".');
}
$title = $title ?? null;
$footer = $footer ?? null;
$size = $size ?? 'md';
$sizeClass = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
][$size] ?? 'max-w-lg';
?>
<div x-data="taModal()"
     @open-modal.window="if ($event.detail === '<?php echo ui_attr($id); ?>') show()"
     @keydown.escape.window="hide()"
     x-show="open"
     x-cloak>
  <div class="ta-modal-backdrop" @click="hide()"></div>
  <div class="ta-modal-panel <?php echo ui_attr($sizeClass); ?>"
       role="dialog"
       aria-modal="true"
       <?php if ($title): ?>aria-labelledby="<?php echo ui_attr($id); ?>-title"<?php endif; ?>
       x-trap.inert.noscroll="open">
    <?php if ($title): ?>
      <header class="mb-4 flex items-center justify-between">
        <h2 id="<?php echo ui_attr($id); ?>-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">
          <?php echo ui_text($title); ?>
        </h2>
        <button type="button" @click="hide()" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/[0.05]"
                aria-label="<?php echo ui_attr(__('ui.button.close', [], null, 'Close')); ?>">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 6 18 18M6 18 18 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      </header>
    <?php endif; ?>

    <div><?php if (isset($body) && $body instanceof Closure) $body(); ?></div>

    <?php if ($footer instanceof Closure): ?>
      <footer class="mt-6 flex items-center justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
        <?php $footer(); ?>
      </footer>
    <?php endif; ?>
  </div>
</div>
