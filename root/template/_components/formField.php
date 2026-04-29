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

$inputClass = "w-full rounded border border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary text-black dark:text-white";
if ($error) $inputClass .= " border-danger";
?>
<div class="mb-4">
  <label for="<?php echo ui_attr($id); ?>" class="mb-2.5 block font-medium text-black dark:text-white">
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
            class="<?php echo ui_attr($inputClass); ?>"
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
    <p id="<?php echo ui_attr($id); ?>-help" class="form-help"><?php echo ui_text($help); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p id="<?php echo ui_attr($id); ?>-error" class="form-error" role="alert"><?php echo ui_text($error); ?></p>
  <?php endif; ?>
</div>
