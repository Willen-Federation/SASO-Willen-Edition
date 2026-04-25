<?php
namespace saso\category;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class ListOutput implements DTO
{
    use Getter;
    /** @param Either<Each<\saso\entity\Category>> $tree */
    /** @param Either<int> $clicked */
    public function __construct(
        private Either $tree,
        private Either $clicked,
    )
    {
    }
}