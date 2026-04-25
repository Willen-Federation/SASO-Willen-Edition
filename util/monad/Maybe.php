<?php
namespace saso\util\monad;

abstract class Maybe implements Monad
{
    public static function just($a): Maybe
    {
        return new Just($a);
    }
    public static function nothing(): Maybe
    {
        return new Nothing();
    }
    public static function fromNullable($a): Maybe
    {
        return $a !== false ? self::just($a) : self::nothing();
    }
    public static function of($a): Maybe
    {
        return self::just($a);
    }
    public abstract function map(\Closure $f): Maybe;
    public abstract function getOrElse($other);
    public abstract function filter(\Closure $f): Maybe;
    public function flatMap(\Closure $f)
    {
        return $this->map($f)->join();
    }
}
