<?php
namespace saso\feature;

use saso\entity\Shelf;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

/** @property Either<string> $shelf */
final class ShelfController implements GettableController, DTO
{
    use Getter;
    private Either $shelf;
    public function __construct(
        array $post,
    )
    {
        $this->shelf = Either::fromNullable(Shelf::numberConstraint($post['number']??''))->filter(
            fn($v)=>!empty($v)
        );
    }
}
