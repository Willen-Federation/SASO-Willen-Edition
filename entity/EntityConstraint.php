<?php

namespace saso\entity;

use saso\util\monad\Either;

final class EntityConstraint
{
    public static function requiredStringConstraint(string $name, int $length): Either
    {
        return Either::of($name)
            ->filter(fn($v)=>!empty($v))
            ->filter(fn($v)=>mb_strlen($v)<=$length);
    }
    public static function optionalNoteConstraint(string $note, int $length): Either
    {
        return Either::of($note)
            ->filter(fn($v)=>mb_strlen($v)<=$length);
    }
}
