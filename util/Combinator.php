<?php
namespace saso\util;

final class Combinator
{
    /**
    * tap(...fn(T)=>void): fn(...fn(T)=>T: T=>T)
    *
    * $logger = Combinator::tap(fn($v)=>var_dump($v));
    * $foo = $logger(fn($v)=>foo($v));
    * $output = $foo($input);
    */
    public static function tap(\Closure ...$effectors): \Closure
    {
        return fn(\Closure ...$funcs)=>fn($val)=>array_reduce(
            //最後の関数適用後の副作用は発生したいので、thunk関数を追加。
            array_merge($funcs, [fn($val)=>$val]),
            function ($carry, $item) use ($funcs, $effectors) {
                array_map(
                    //最初の関数適用前は入力と同じなので副作用を発生させない。
                    fn($effector)=>$funcs[0]===$item?null:$effector($carry),
                    $effectors
                );
                return $item($carry);
            },
            $val,
        );
    }
    public static function fork(\Closure $join, \Closure $func1, \Closure $func2): \Closure
    {
        return fn($val)=>$join($func1($val), $func2($val));
    }
}
