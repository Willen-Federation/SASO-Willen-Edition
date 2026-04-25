<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;
use saso\util\monad\Maybe;

/**
 * @param Either<Item> $item
 * @param Maybe<Either<array<string>>> $colors
 * @param Maybe<Either<array<string>>> $sizes
 */
final class AddFeatureOutput implements DTO
{
    use Getter;
    public function __construct(
        private Either $item,
        private Maybe $colors,
        private Maybe $sizes,
        private bool $isValidAmount,
    )
    {
    }
}