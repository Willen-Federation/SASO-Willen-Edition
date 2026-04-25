<?php

namespace saso\entity;

final class Config
{
    public static function sheetAmountConstraint(array $config): int
    {
        return filter_var(
            $config['sheetAmount']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>100,
                    'min_range'=>1,
                    'max_range'=>9999,
                ]
            ],
        );
    }
    public static function outputRowConstraint(array $config): int
    {
        return filter_var(
            $config['outputRow']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>10,
                    'min_range'=>1,
                    'max_range'=>99,
                ],
            ]
        );
    }
}