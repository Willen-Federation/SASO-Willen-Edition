<?php $this->content = function($v) { ?>

<?php echo json_encode([
    'shortName'=>$v->shortName,
]); ?>

<?php } ?>
