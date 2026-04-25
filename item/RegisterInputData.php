<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;
use saso\util\monad\Maybe;

final class RegisterInputData implements DTO
{
    use Getter;
    /**
     * @param Either<string> $name
     * @param Maybe<string> $categoryId
     * @param Either<int> $price
     * @param Either<Generator<RegisterColorInputData>> $colors
     * @param Either<Generator<RegisterSizeInputData>> $sizes
     * @param bool $pla
     * @param Either<string> $plaNote
     * @param bool $paper
     * @param Either<string> $paperNote
     * @param \DateTime $now
     * @param bool $validFeaturesAmount
     */
    public function __construct(
        private Either $name,
        private Maybe $categoryId,
        private Either $price,
        private Either $colors,
        private Either $sizes,
        private bool $pla,
        private Either $plaNote,
        private bool $paper,
        private Either $paperNote,
        private \DateTime $now,
        private bool $validFeaturesAmount,
    )
    {
    }
}