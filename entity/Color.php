<?php

namespace saso\entity;

use saso\util\monad\Either;

final class Color
{
    public function __construct(
        private Item $item,
        private string $code,
        private string $name,
        private ?string $imageType='',
    )
    {
    }
    public function setImageType(string $type): self
    {
        $this->imageType = $type;
        return $this;
    }
    public static function codeConstraint(int $code): Either
    {
        return Feature::validateCode($code, 100);
    }
    public static function nameConstraint(string $name): Either
    {
        return EntityConstraint::requiredStringConstraint($name, 50);
    }
    public function __get($name)
    {
        return $this->$name;
    }
}