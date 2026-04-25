<?php $this->content = function($v) { ?>

<?php echo json_encode([
    'children'=>$v->tree,
    'selected'=>$v->clicked,
]); ?>

<?php }; ?>
