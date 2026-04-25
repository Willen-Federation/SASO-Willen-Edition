<?php
namespace saso\item;

use saso\entity\Item;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class RowOutput implements DTO
{
    use Getter;
    public function __construct(
        private Item $item,
        private Either $iv,
        private Either $colors,
        private Either $sizes,
    )
    {
    }
}
