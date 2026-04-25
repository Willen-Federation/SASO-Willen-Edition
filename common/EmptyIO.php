<?php
namespace saso\common;

use saso\framework\DTO;

final class EmptyIO implements DTO
{
    public function __get($prop)
    {
        return null;
    }
}
