<?php $this->content = function($v) { ?>

<?php
foreach($v->insides as $inside) {
?>
<tr>
<td><input class="archiveAllCheckbox" type="checkbox" name="archive"></td><?php $inside('item', 'row') ?>
</tr>
<?php
}
?>

<?php }; ?>
