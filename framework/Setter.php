<?php
namespace saso\framework;

trait Setter
{
    public function __call(string $name, array $args)
    {
        return function($v) use ($name, $args) {
            $this->$name = $args[0]($v);
            return $v;
        };
    }
}