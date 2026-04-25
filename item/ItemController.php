<?php
namespace saso\item;

use saso\common\EmptyIO;
use saso\entity\Item;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\DirectInput;
use saso\framework\GetterAndAnother;
use saso\util\monad\Either;

/**
 * @property Either<string> $id
 */
final class ItemController implements Controller, DTO
{
    use DirectInput;
    use GetterAndAnother;
    private Either $id;
    private DTO $another;
    public function __construct(
        array $query,
        ?GettableController $anotherCtrl=null,
    )
    {
        $this->id = Item::idConstraint($query['item']??'');
        $this->another = $anotherCtrl??new EmptyIO();
    }
}
