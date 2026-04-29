<?php $this->title = __('ui.sidebar.home', [], null, 'Home'); ?>
<?php $this->content = function ($v) { ?>

<?php 
($v->inside)('start', 'menu'); 
?>

<?php }; ?>
