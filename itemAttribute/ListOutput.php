<?php
namespace saso\itemAttribute;

use saso\framework\DTO;
use saso\util\monad\Either;

final class ListOutput implements DTO
{
    public function __construct(
        public readonly Either $definitions,
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->$name;
    }
}
