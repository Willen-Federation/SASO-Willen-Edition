<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

/** @property Either<int | null> $categoryId */
final class ChangeCategoryController implements GettableController, DTO
{
    use Getter;
    private Either $categoryId;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $this->categoryId = Either::fromNullable($post['categoryId']??false)->flatMap(
            fn($v)=>filter_var($v, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE)
        );
    }
}
