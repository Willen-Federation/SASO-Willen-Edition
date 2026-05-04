<?php $this->content = function($v) { ?>

<?php foreach ($v->labels as $label){ ?>
  <li>
    <label class="form-check">
      <input type="radio" class="form-check-input" name="labelName" id="radio<?php echo $label->name; ?>" value="<?php echo $label->name; ?>">
      <span class="form-check-label"><?php echo $label->name; ?></span>
    </label>
  </li>
<?php } ?>

<?php }; ?>
