<?php
namespace saso\feature;

use saso\common\EmptyIO;
use saso\entity;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\DirectInput;
use saso\framework\GetterAndAnother;
use saso\util\monad\Either;

/**
 * @property Either<string> $id
 * @property Either<string> $color
 * @property Either<string> $size
 */
final class FeatureController implements Controller, DTO
{
    use DirectInput;
    use GetterAndAnother;
    private Either $id;
    private Either $color;
    private Either $size;
    private DTO $another;
    public function __construct(
        array $query,
        ?GettableController $anotherCtrl=null,
    )
    {
        $this->id = entity\Item::idConstraint($query['item']??'');
        $feature = fn($taxonomy)=>entity\Feature::codeConstraint($query[$taxonomy]??'');
        $this->color = $feature('color');
        $this->size = $feature('size');
        $this->another = $anotherCtrl??new EmptyIO();
    }
}
