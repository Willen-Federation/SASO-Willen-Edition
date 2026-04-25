<?php
namespace saso\util;

final class Each
{
    public static function m(): \Closure
    {
        return function(\Traversable|array $t2): \Generator {
            foreach($t2 as $t) {
                yield from $t;
            }
        };
    }
    public static function i(): \Closure
    {
        return fn($u)=>yield $u;
    }
    public static function t($x): \Generator
    {
        if($x instanceof \Traversable || is_array($x)) {
            yield from $x;
        } else {
            yield $x;
        }
    }
    public static function tf(\Closure $f): \Closure
    {
        return function(\Traversable|array $xs) use ($f): \Generator {
            foreach($xs as $x) {
                yield $f($x);
            }
        };
    }
    public static function exec(\Closure $f): \Closure
    {
        return function(\Traversable|array $t) use ($f): void {
            foreach ($t as $i) {
                $f($i);
            }
        };
    }
}
