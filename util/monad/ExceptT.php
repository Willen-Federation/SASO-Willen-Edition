<?php
namespace saso\util\monad;

class ExceptT implements Monad
{
    public function __construct(
        private Either $e,
        private Monad $m
    )
    {
    }
    public static function lift(Either $e): \Closure
    {
        return fn(Monad $m)=>new self($e, $m);
    }
    public function runExceptT(): Monad
    {
        return $this->m->flatMap(
            fn($v)=>$this->e->flatMap(
                function($a) use ($v) {
                    $v($a);
                    return $a;
                }
            )
        );
    }
    /** @param fn(Monad<Either> $m):Monad<Either> $f */
    public function map(\Closure $f): self
    {
        return self::lift($this->e)(
            $f($this->runExceptT())
        );
    }
    public function withExceptT(\Closure $f): self
    {
        return self::lift($this->e->flatMap($f))($this->m);
    }
    public function chain(\Closure $f)
    {
        return $f($this->m);
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
