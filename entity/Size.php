<?php

namespace saso\entity;

use saso\util\monad\Either;

final class Size
{
    public function __construct(
        private Item $item,
        private string $code,
        private string $name,
        private int $orderNumber,
    )
    {
        
    }
    public static function codeConstraint(int $code): Either
    {
        return Feature::validateCode($code, 100);
    }
    public static function nameConstraint(string $name): Either
    {
        return EntityConstraint::requiredStringConstraint($name, 50);
    }
    public static function orderNumberConstraint(int $number): Either
    {
        return Either::of($number)
            ->filter(fn($v)=>$v>=0&&$v<100);
    }
    public function __get($name)
    {
        return $this->$name;
    }
}