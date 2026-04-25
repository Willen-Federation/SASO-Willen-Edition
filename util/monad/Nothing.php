<?php
namespace saso\util\monad;

final class Nothing extends Maybe
{
    public function map(\Closure $f): Maybe
    {
        return $this;
    }
    public function getOrElse($other)
    {
        return $other;
    }
    public function filter(\Closure $f): Maybe
    {
        return $this;
    }
    public function chain(\Closure $f)
    {
        return $this;
    }
    public function join()
    {
        return $this;
    }
}
