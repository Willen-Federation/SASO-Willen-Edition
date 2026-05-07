<?php
namespace saso\util\monad;

class Left extends Either
{
    public function map(\Closure $f): Either
    {
        return $this;
    }
    public function getOrElse($other)
    {
        return $other;
    }
    public function orElse(\Closure $f)
    {
        return $f($this->value);
    }
    public function chain(\Closure $f)
    {
        return $this;
    }
    public function getOrElseThrow($a)
    {
        throw new \Exception($a);
    }
    public function filter(\Closure $f): Either
    {
        return $this;
    }
    public function isRight(): bool
    {
        return false;
    }
    public function isLeft(): bool
    {
        return true;
    }
    public function join()
    {
        return $this;
    }
}
