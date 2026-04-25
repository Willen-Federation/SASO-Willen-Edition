<?php
namespace saso\util\monad;

interface Monad
{
    public function map(\Closure $f);
    public function chain(\Closure $f);
    public function join();
    public function flatMap(\Closure $f);
}
