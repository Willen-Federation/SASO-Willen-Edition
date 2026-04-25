<?php
namespace saso\shelf;

use saso\entity\Shelf;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class SingleController implements Controller, DTO
{
    use DirectInput;
    use Getter;
    private Either $shelves;
    public function __construct(
        array $query,
    )
    {
        $number = Shelf::numberConstraint($query['number']??'');
        $this->shelves = Either::fromNullable(
            empty($number)?false:$number
        );
    }
}