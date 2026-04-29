<?php
namespace saso\util\monad;

abstract class Either implements Monad
{
    protected $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public static function left($a): Either
    {
        return new Left($a);
    }
    public static function right($a): Either
    {
        return new Right($a);
    }
    public static function fromNullable($val): Either
    {
        return $val !== false ? Either::right($val) : Either::left($val);
    }
    public static function of($a): Either
    {
        return Either::right($a);
    }
    public abstract function getOrElse($other);
    public abstract function orElse(\Closure $f);
    public abstract function getOrElseThrow($a);
    public abstract function filter(\Closure $f): Either;
    public abstract function isRight(): bool;
    public abstract function isLeft(): bool;
    public function flatMap(\Closure $f)
    {
        return $this->map($f)->join();
    }
}
