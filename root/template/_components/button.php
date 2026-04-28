<?php
/*
 * Button partial. Args:
 *   - label:    string (required)
 *   - variant?: 'primary'|'secondary'|'danger'|'success'|'ghost'  (default 'primary')
 *   - type?:    'submit'|'button'|'reset'|'link'  (default 'button'; 'link' renders <a>)
 *   - href?:    string (when type='link')
 *   - size?:    'sm'|'md'|'lg' (default 'md')
 *   - icon?:    string (raw SVG markup)
 *   - disabled?: bool
 *   - name?:    string (form button)
 *   - value?:   string (form button)
 *   - extraClass?: string
 *   - aria?:    array<string,string>  ['label'=>...]
 */
if (empty($label)) {
    throw new RuntimeException('ui("button") requires "label".');
}
$variant     = $variant ?? 'primary';
$type        = $type ?? 'button';
$href        = $href ?? '#';
$size        = $size ?? 'md';
$icon        = $icon ?? null;
$disabled    = !empty($disabled);
$name        = $name ?? null;
$valueAttr   = $value ?? null;
$extraClass  = $extraClass ?? '';
$aria        = $aria ?? [];
$id          = $id ?? null;

$variantClass = [
    'primary'   => 'btn-primary',
    'secondary' => 'btn-secondary',
    'danger'    => 'btn-danger',
    'success'   => 'btn-success',
    'ghost'     => 'btn-ghost',
][$variant] ?? 'btn-primary';

$sizeClass = [
    'sm' => 'btn-sm',
    'md' => '',
    'lg' => 'btn-lg',
][$size] ?? '';

$cls = trim('btn ' . $variantClass . ' ' . $sizeClass . ' ' . $extraClass);

$ariaAttrs = '';
foreach ($aria as $k => $v) {
    $ariaAttrs .= ' aria-' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '="' . ui_attr($v) . '"';
}

if ($type === 'link') { ?>
  <a <?php if ($id !== null): ?>id="<?php echo ui_attr($id); ?>"<?php endif; ?>
     href="<?php echo ui_attr($href); ?>" class="<?php echo ui_attr($cls); ?>" <?php echo $ariaAttrs; ?>>
    <?php echo $icon ?? ''; ?>
    <span><?php echo ui_text($label); ?></span>
  </a>
<?php } else { ?>
  <button type="<?php echo ui_attr($type); ?>"
          <?php if ($id !== null): ?>id="<?php echo ui_attr($id); ?>"<?php endif; ?>
          class="<?php echo ui_attr($cls); ?>"
          <?php if ($disabled): ?>disabled<?php endif; ?>
          <?php if ($name !== null): ?>name="<?php echo ui_attr($name); ?>"<?php endif; ?>
          <?php if ($valueAttr !== null): ?>value="<?php echo ui_attr($valueAttr); ?>"<?php endif; ?>
          <?php echo $ariaAttrs; ?>>
    <?php echo $icon ?? ''; ?>
    <span><?php echo ui_text($label); ?></span>
  </button>
<?php } ?>
