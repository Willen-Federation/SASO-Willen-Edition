<?php
namespace saso\category;

use saso\entity;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class NameController implements GettableController, DTO
{
    use Getter;
    private Either $name;
    public function __construct(
        array $post,
    )
    {
        $this->name = entity\Category::nameConstraint($post['categoryName']??'');
    }
}