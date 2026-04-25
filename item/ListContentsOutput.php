<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class ListContentsOutput implements DTO
{
    use Getter;
    /** @param Either<Generetor<Closure>> $item */
    public function __construct(
        private Either $items,
    )
    {
    }
}

