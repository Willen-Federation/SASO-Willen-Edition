<?php $this->content = function($v) { ?>

<?php foreach ($v->labels as $label){ ?>
<li class="list-unstyled">
<input type="radio" name="labelName" id="radio<?php echo $label->name; ?>" value="<?php echo $label->name; ?>">
<label for="radio<?php echo $label->name; ?>"><?php echo $label->name; ?></label>
</li>
<?php } ?>

<?php }; ?>
