<?php
namespace saso\util;

final class M
{
    public static function id($a): array
    {
        return [
            'return'=>fn()=>M::id($a),
            '>>='=>fn($f)=>M::id($f($a)),
            'get'=>fn()=>$a
        ];
    }
    public static function left($a): array
    {
        return [
            'return'=>fn()=>M::left($a),
            'when'=>fn()=>false,
            'map'=>fn($f)=>M::left($a),
            'join'=>fn()=>($a['return']??fn()=>M::left($a))(),
            '>>='=>fn($f)=>M::left($a),
            'getOrElse'=>fn($else)=>$else,
        ];
    }
    public static function right($a): array
    {
        return [
            'return'=>fn()=>M::right($a),
            'when'=>fn($f)=>$f($a)?M::right($a):M::left($a),
            'map'=>fn($f)=>M::right($f($a)),
            'join'=>fn()=>($a['return']??fn()=>M::right($a))(),
            '>>='=>fn($f)=>M::right($a)['map']($f)['join'](),
            'getOrElse'=>fn()=>$a,
        ];
    }
}