<?php $this->content = function($v) { ?>

<?php echo json_encode([
    'labelName'=>$v->label->name,
    'marginTop'=>$v->label->marginTop,
    'marginLeft'=>$v->label->marginLeft,
    'width'=>$v->label->width,
    'height'=>$v->label->height,
    'intervalColumn'=>$v->label->intervalColomn,
    'intervalRow'=>$v->label->intervalRow,
]); ?>

<?php } ?>
