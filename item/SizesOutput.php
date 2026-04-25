<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class SizesOutput implements DTO
{
    use Getter;
    public function __construct(
        private Either $item,
        private Either $sizes,
    )
    {
    }
}
