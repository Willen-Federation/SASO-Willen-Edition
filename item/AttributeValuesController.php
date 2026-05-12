<?php
namespace saso\item;

use saso\common\EmptyIO;
use saso\entity\Item;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\GetterAndAnother;
use saso\util\monad\Either;

final class AttributeValuesController implements Controller, DTO
{
    use DirectInput;
    use GetterAndAnother;

    private Either $id;
    private Either $attrValues;
    private DTO $another;

    public function __construct(
        array $query,
        array $post = [],
        ?GettableController $anotherCtrl = null,
    ) {
        $this->id = Item::idConstraint($query['item'] ?? '');

        $rawAttr = $post['attr'] ?? [];
        $this->attrValues = is_array($rawAttr)
            ? Either::of($rawAttr)
            : Either::left('attr must be an array.');

        $this->another = $anotherCtrl ?? new EmptyIO();
    }
}
