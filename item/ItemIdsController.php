<?php
namespace saso\item;

use saso\common\EmptyIO;
use saso\entity;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\DirectInput;
use saso\framework\GetterAndAnother;
use saso\util\Each;
use saso\util\monad\Either;

/** @property Either<Each<Either<string>>> $ids */
final class ItemIdsController implements Controller, DTO
{
    use DirectInput;
    use GetterAndAnother;
    private Either $ids;
    private DTO $another;
    public function __construct(
        array $post,
        ?GettableController $anotherCtrl=null,
    )
    {
        $archivings = array_map(
            fn($v)=>$post[$v],
            array_filter(
                array_keys($post),
                fn($v)=>preg_match('/^archive\d{8}$/', $v)===1
            )
        );
        $this->ids = Either::of(Each::t($archivings))->flatMap(
            Each::tf(fn($v)=>entity\Item::idConstraint($v))
        );
        $this->another = $anotherCtrl??new EmptyIO();
    }
}
