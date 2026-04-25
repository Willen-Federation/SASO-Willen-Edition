<?php
namespace saso\item;

use saso\util\monad\Either;

final class RegisterSizeInputData
{
    public function __construct(
        private Either $code,
        private Either $name,
        private Either $orderNumber,
    )
    {
    }
    public function __get($prop)
    {
        return $this->$prop;
    }
}