<?php
namespace saso\item;

use saso\entity\Item;
use saso\entity\ItemVar;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class RegisterOutputData implements DTO
{
    use Getter;
    /**
     * @param Either<Generator<Color>> $colors
     * @param Either<Generator<Size>> $sizes
     */
    public function __construct(
        private Item $item,
        private Either $colors,
        private Either $sizes,
        private ItemVar $itemVar,
        private bool $validFeaturesAmount,
    )
    {
    }
}