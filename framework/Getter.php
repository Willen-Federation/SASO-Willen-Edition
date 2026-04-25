<?php
namespace saso\framework;

trait Getter
{
    public function __get($prop)
    {
        return $this->$prop;
    }
}