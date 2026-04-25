<?php
namespace saso\label;

use saso\entity\Feature;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\framework\GettableController;
use saso\util\monad\Either;

final class ShortNameController implements GettableController, DTO
{
    use Getter;
    private Either $fullCode;
    public function __construct(
        private array $post,
    )
    {
        $this->fullCode = Feature::fullCodeConstraint($post['fullCode']??'');
    }
}