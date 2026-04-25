<?php
namespace saso\entity;

final class Shelf
{
    public function __construct(
        private Feature $feature,
        private string $number,
    )
    {
    }
    public function __get($name)
    {
        return $this->$name;
    }
    public static function numberConstraint(string $number): string
    {
        return filter_var(
            strtoupper($number),
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>'',
                    'regexp'=>'/^[0-9A-Z-]{1,15}$/'
                ]
            ],
        );
    }
}