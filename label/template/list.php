<?php $this->content = function($v) { ?>

<?php foreach ($v->labels as $label){ ?>
  <li>
    <label class="form-check">
      <input type="radio" class="form-check-input" name="labelName" id="radio<?php echo htmlspecialchars($label->name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($label->name, ENT_QUOTES, 'UTF-8'); ?>">
      <span class="form-check-label"><?php echo htmlspecialchars($label->name, ENT_QUOTES, 'UTF-8'); ?></span>
    </label>
  </li>
<?php } ?>

<?php }; ?>
