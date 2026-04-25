<?php
namespace saso\item;

use saso\util\monad\Either;

final class RegisterColorInputData
{
    public function __construct(
        private Either $code,
        private Either $name,
    )
    {
    }
    public function __get($prop)
    {
        return $this->$prop;
    }
}