<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

/** @property Either<int> $price */
final class ChangePriceController implements GettableController, DTO
{
    use Getter;
    private Either $price;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $this->price = entity\ItemVar::priceConstraint($post['price']??'');
    }
}
