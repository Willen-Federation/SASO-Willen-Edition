<?php

namespace saso\util\monad;

final class IO implements Monad
{
    private $effect;
    private function __construct(\Closure $effect)
    {
        $this->effect = $effect;
    }
    public static function of($a): IO
    {
        return new IO(fn()=>$a);
    }
    public static function from(\Closure $f): IO
    {
        return new IO($f);
    }
    public function map(\Closure $f): IO
    {
        return new IO(fn()=>$f(($this->effect)()));
    }
    public function chain(\Closure $f)
    {
        return $f(($this->effect)());
    }
    public function run()
    {
        return ($this->effect)();
    }
    public function join()
    {
        return $this;
    }
    public function flatMap(\Closure $f)
    {
        return $this->map($f)->join();
    }
}
