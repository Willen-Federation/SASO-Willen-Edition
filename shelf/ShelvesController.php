<?php
namespace saso\shelf;

use saso\entity\Shelf;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\Each;
use saso\util\monad\Either;

final class ShelvesController implements Controller, DTO
{
    use DirectInput;
    use Getter;
    private Either $shelves;
    public function __construct(
        array $post,
    )
    {
        $this->shelves = Either::fromNullable(filter_var(
            $post['amount']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>1,
                    'min_range'=>1,
                    'max_range'=>100,
                ]
            ],
        ))->flatMap(
            fn($v)=>Each::t(range(0, $v-1))
        )->flatMap(
            Each::tf(fn($v)=>Shelf::numberConstraint($post['shelf'.$v]??''))
        );
    }
}