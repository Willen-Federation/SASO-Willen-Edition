<?php $this->content = function($v) { ?>
<?php echo json_encode(['definitions' => $v->definitions]); ?>
<?php }; ?>
