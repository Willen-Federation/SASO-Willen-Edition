<?php
namespace saso\util;

final class Verifier
{
    public static function verify(array $post): bool
    {
        $isConfirmation = fn($i)=>preg_match('/Confirm$/', $i)===1;
        $userInputKeys = array_filter(
            array_keys($post),
            fn($key)=>!$isConfirmation($key)
        );
        $confirmKeys = array_filter(
            array_keys($post),
            fn($key)=>$isConfirmation($key)
        );
        return  array_reduce(
            $userInputKeys,
            //userInputがあるのにConfirmがないのは不正
            fn($carry, $key)=>array_key_exists($key.'Confirm', $post)&&$carry,
            true
        )
        && array_reduce(
            $confirmKeys,
            function($carry, $key) use ($post) {
                $inputKey = preg_replace('/Confirm$/', '', $key);
                //userInputがないなら空欄とみなす。チェックボックス用。
                if(array_key_exists($inputKey, $post)) {
                    $input = $post[$inputKey];
                } else {
                    $input = '';
                }
                return ($post[$key]===$input)&&$carry;
            },
            true
        );
    }
}
