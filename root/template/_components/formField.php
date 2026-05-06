<?php
/*
 * Form field partial. Args:
 *   - name:        string (required)
 *   - label:       string (required, throws if missing)
 *   - type?:       string (default 'text'; 'textarea' renders <textarea>; 'select' uses $options)
 *   - id?:         string (default = name)
 *   - value?:      string|int
 *   - required?:   bool
 *   - placeholder?: string
 *   - autocomplete?: string
 *   - help?:       string
 *   - error?:      string
 *   - options?:    array<string,string> (only used when type='select')
 *   - rows?:       int (textarea, default 4)
 */

if (!isset($name) || $name === '') {
    throw new RuntimeException('ui("formField") requires "name".');
}
if (empty($label)) {
    throw new RuntimeException(sprintf('ui("formField") requires "label" for input "%s".', $name));
}

$id           = $id ?? $name;
$type         = $type ?? 'text';
$value        = $value ?? '';
$required     = !empty($required);
$placeholder  = $placeholder ?? null;
$autocomplete = $autocomplete ?? null;
$help         = $help ?? null;
$error        = $error ?? null;
$options      = $options ?? [];
$rows         = $rows ?? 4;

$ariaDescribedBy = [];
if ($help)  $ariaDescribedBy[] = $id . '-help';
if ($error) $ariaDescribedBy[] = $id . '-error';
$inputClass = 'form-control' . ($error ? ' is-invalid' : '');
$selectClass = 'form-select' . ($error ? ' is-invalid' : '');
?>
<div class="mb-3">
  <label for="<?php echo ui_attr($id); ?>" class="form-label">
    <?php echo ui_text($label); ?>
    <?php if ($required): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
  </label>

  <?php if ($type === 'textarea'): ?>
    <textarea id="<?php echo ui_attr($id); ?>"
              name="<?php echo ui_attr($name); ?>"
              rows="<?php echo (int) $rows; ?>"
              class="<?php echo ui_attr($inputClass); ?>"
              <?php if ($placeholder): ?>placeholder="<?php echo ui_attr($placeholder); ?>"<?php endif; ?>
              <?php if ($required): ?>required<?php endif; ?>
              <?php if ($error):    ?>aria-invalid="true"<?php endif; ?>
              <?php if ($ariaDescribedBy): ?>aria-describedby="<?php echo ui_attr(implode(' ', $ariaDescribedBy)); ?>"<?php endif; ?>><?php echo ui_text((string) $value); ?></textarea>

  <?php elseif ($type === 'select'): ?>
    <select id="<?php echo ui_attr($id); ?>"
            name="<?php echo ui_attr($name); ?>"
            class="<?php echo ui_attr($selectClass); ?>"
            <?php if ($required): ?>required<?php endif; ?>
            <?php if ($error):    ?>aria-invalid="true"<?php endif; ?>
            <?php if ($ariaDescribedBy): ?>aria-describedby="<?php echo ui_attr(implode(' ', $ariaDescribedBy)); ?>"<?php endif; ?>>
      <?php foreach ($options as $optVal => $optLabel): ?>
        <option value="<?php echo ui_attr((string) $optVal); ?>" <?php echo ((string) $optVal === (string) $value) ? 'selected' : ''; ?>>
          <?php echo ui_text((string) $optLabel); ?>
        </option>
      <?php endforeach; ?>
    </select>

  <?php else: ?>
    <input type="<?php echo ui_attr($type); ?>"
           id="<?php echo ui_attr($id); ?>"
           name="<?php echo ui_attr($name); ?>"
           value="<?php echo ui_attr((string) $value); ?>"
           class="<?php echo ui_attr($inputClass); ?>"
           <?php if ($placeholder):  ?>placeholder="<?php echo ui_attr($placeholder); ?>"<?php endif; ?>
           <?php if ($autocomplete): ?>autocomplete="<?php echo ui_attr($autocomplete); ?>"<?php endif; ?>
           <?php if ($required):     ?>required<?php endif; ?>
           <?php if ($error):        ?>aria-invalid="true"<?php endif; ?>
           <?php if ($ariaDescribedBy): ?>aria-describedby="<?php echo ui_attr(implode(' ', $ariaDescribedBy)); ?>"<?php endif; ?>>
  <?php endif; ?>

  <?php if ($help): ?>
    <div id="<?php echo ui_attr($id); ?>-help" class="form-text"><?php echo ui_text($help); ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div id="<?php echo ui_attr($id); ?>-error" class="invalid-feedback d-block" role="alert"><?php echo ui_text($error); ?></div>
  <?php endif; ?>
</div>
