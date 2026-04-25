<?php $this->content = function($v) { ?>

<?php
foreach($v->insides as $inside) {
?>
<tr>
<?php $inside('item', 'row') ?>
</tr>
<?php
}
?>

<?php }; ?>
