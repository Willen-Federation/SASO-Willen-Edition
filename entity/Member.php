<?php

namespace saso\entity;

use saso\util\monad\Either;

final class Member
{
    public function __construct(
        private string $id,
        private string $name,
        private string $password,
    )
    {
    }
    public function __get($name)
    {
        return $this->$name;
    }
    public static function idConstraint(string $id): Either
    {
        return Either::fromNullable(filter_var(
            $id,
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9a-zA-Z-_]{8,20}$/'
                ]
            ]
        ));
    }
    public static function nameConstraint(string $name): Either
    {
        return EntityConstraint::requiredStringConstraint($name, 50);
    }
    public static function passwordConstraint(string $password): Either
    {
        return Either::of($password)
            ->filter(fn($v)=>!empty($v))
            ->filter(fn($v)=>mb_strlen($v)<=20&&mb_strlen($v)>=8)
            ->filter(fn($v)=>preg_match('/[^0-9a-zA-Z]/', $v)===0);
    }
    public static function hashed(string $raw): string
    {
        $hashed = hash('sha256', $raw);
        $salted = 'stok-administra_sistemo'.$hashed.'plej_simpla';
        return array_reduce(
            range(1,1000),
            fn($carry, $item)=>hash('sha256', $carry),
            $salted,
        );
    }
}
