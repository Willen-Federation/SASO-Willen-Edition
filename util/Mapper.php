<?php
namespace saso\util;

final class Mapper
{
    public static function exec(array $pairs): array
    {
        //[[a,b],[c,d],[e]] -> [a=>b,c=>d]
        return array_reduce(
            array_filter($pairs, fn($item)=>count($item)===2),
            fn($carry, $item)=>array_merge($carry, [$item[0]=>$item[1]]),
            [],
        );
    }
    //public static function exec(array $array): array
    //{
    //    if(count($array)%2 === 1) array_pop($array);
    //    if(count($array) === 0) return [];
    //    return array_reduce(
    //        count($array)===2?[0]:range(0, count($array)-1, 2),
    //        fn($carry, $item)=>array_merge($carry, [$array[$item]=>$array[$item+1]]),
    //        [],
    //    );
    //}
}
