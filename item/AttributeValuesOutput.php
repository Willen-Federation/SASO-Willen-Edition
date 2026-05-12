<?php
namespace saso\item;

use saso\framework\DTO;
use saso\util\monad\Either;

final class AttributeValuesOutput implements DTO
{
    public function __construct(
        public readonly Either $item,
        public readonly Either $attributes,
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->$name;
    }
}
