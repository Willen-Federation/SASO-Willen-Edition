<?php
namespace saso\util\monad;

class Right extends Either
{
    public function map(\Closure $f): Either
    {
        return Either::of($f($this->value));
    }
    public function getOrElse($other)
    {
        return $this->value;
    }
    public function orElse(\Closure $f)
    {
        return $this;
    }
    public function chain(\Closure $f)
    {
        return $f($this->value);
    }
    public function getOrElseThrow($a)
    {
        return $this->value;
    }
    public function filter(\Closure $f): Either
    {
        return Either::fromNullable($f($this->value)?$this->value:false);
    }
    public function isRight(): bool
    {
        return true;
    }
    public function isLeft(): bool
    {
        return false;
    }
    public function join()
    {
        if(! $this->value instanceof Monad) {
            return $this;
        }
        return $this->value->join();
    }
}
