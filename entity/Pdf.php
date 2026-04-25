<?php
namespace saso\entity;

use saso\util\monad\Either;

final class Pdf
{
    /** @return Either<string> */
    public static function shortenName( Either $label, Either $feature): Either
    {
        return $label->flatMap(
            fn($l)=>$feature->flatMap(
                function($f) use ($l) {
                    $array = [
                        $f->item->name,
                        $f->color->name,
                        $f->size->name,
                    ];
                    $colorCode = $f->color->code;
                    $judge = [false, false, false];
                    $index = 0;
                    $resuc = function($str) {
                        if(mb_strlen($str)-1 > 1) {
                            return mb_substr($str, 0, mb_strlen($str)-1);
                        } else {
                            return $str;
                        }
                    };
                    $readableText = $array[0].'/'.$array[1].'('.$colorCode.')'.$array[2];
                    while(mb_strlen($readableText)*3.5 > $l->width) {
                        $before = mb_strlen($array[$index%3]);
                        $array[$index%3] = $resuc($array[$index%3]);
                        $readableText = $array[0].'/'.$array[1].'('.$colorCode.')'.$array[2];
                        $after = mb_strlen($array[$index%3]);
                        $judge[$index%3] = $before === $after;
                        $index++;
                        if(count(array_unique($judge)) === 1 && $judge[0]) break;
                    }
                    return $readableText;
                }
            )
        );
    }
}