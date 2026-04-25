<?php
namespace saso\entity;

use saso\util\Each;
use saso\util\monad\Either;

final class Category
{
    private Either $children;
    public function __construct(
        private ?int $id,
        private string $name,
    )
    {
        $this->children = Either::of(Each::t([]));
    }
    public function setChildren(Either $children): self
    {
        $this->children = $children;
        return $this;
    }
    public function __get($prop)
    {
        return $this->$prop;
    }
    public static function nameConstraint(string $name): Either
    {
        return EntityConstraint::requiredStringConstraint($name, 50);
    }
}