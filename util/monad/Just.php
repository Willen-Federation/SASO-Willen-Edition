<?php
namespace saso\util\monad;

final class Just extends Maybe
{
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function map(\Closure $f): Maybe
    {
        return Maybe::fromNullable($f($this->value));
    }
    public function getOrElse($other)
    {
        return $this->value;
    }
    public function filter(\Closure $f): Maybe
    {
        return Maybe::fromNullable($f($this->value) ? $this->value : false);
    }
    /** @param t:Maybe<t> $f */
    public function chain(\Closure $f)
    {
        return $f($this->value);
    }
    public function join()
    {
        if(! $this->value instanceof Monad) {
            return $this;
        }
        return $this->value->join();
    }
}
