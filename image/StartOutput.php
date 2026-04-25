<?php
namespace saso\image;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class StartOutput implements DTO
{
    use Getter;
    public function __construct(
        private Either $color,
        private Either $archive,
    )
    {
    }
}