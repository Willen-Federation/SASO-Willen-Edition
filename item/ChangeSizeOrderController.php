<?php
namespace saso\item;

use saso\entity\Feature;
use saso\entity\Size;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\Each;
use saso\util\monad\Either;

/** @property Either<Each<Either<[string, int]>>> $sizes */
final class ChangeSizeOrderController implements GettableController, DTO
{
    use Getter;
    private Either $sizes;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $this->sizes = Either::of(Each::t(array_filter(
            array_keys($post),
            fn($key)=>preg_match('/size/', $key)
        )))->flatMap(
            Each::tf(fn($v)=>preg_replace('/size/', '', $v))
        )->flatMap(
            Each::tf(fn($v)=>Feature::codeConstraint($v))
        )->flatMap(
            Each::tf(fn($v)=>$v->flatMap(fn($k)=>['key'=>$k, 'value'=>Size::orderNumberConstraint(filter_var(
                $post['size'.$k]??'',
                \FILTER_VALIDATE_INT,
                [
                    'options'=>[
                        'default'=>0,
                    ],
                ]
            ))]))
        )->flatMap(
            Each::tf(fn($v)=>$v->flatMap(
                fn($a)=>$a['value']->flatMap(
                    fn($o)=>['code'=>$a['key'], 'orderNumber'=>$o]
                )
            ))
        );
    }
}