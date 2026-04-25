<?php $this->content = function($v) { ?>

<?php echo json_encode([
    'path'=>$v->path,
]); ?>

<?php } ?>