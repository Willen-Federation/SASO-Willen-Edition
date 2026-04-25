<?php
namespace saso\entity;

use saso\util\monad\Either;

final class Label
{
    private static float $labelWidth = 210;
    private static float $labelHeight = 297;
    public function __construct(
        private string $name,
        private float $marginTop,
        private float $marginLeft,
        private float $width,
        private float $height,
        private float $intervalColomn,
        private float $intervalRow,
    )
    {
    }
    public static function createValidLabel(
        string $name,
        float $marginTop,
        float $marginLeft,
        float $width,
        float $height,
        float $intervalColomn,
        float $intervalRow,

    ): Either
    {
        if((int)$width === 0 || (int)$height === 0) {
            return Either::left('invalid length');
        }
        $colomnlimit = floor((self::$labelWidth - $marginLeft) / ($width + $intervalColomn));
        $rowlimit = floor((self::$labelHeight - $marginTop) / ($height + $intervalRow));
        if(
            self::$labelWidth < $colomnlimit*($width+$intervalColomn)+$marginLeft
            || self::$labelHeight < $rowlimit*($height+$intervalRow)+$marginTop
            || (int)$colomnlimit === 0
            || (int)$rowlimit === 0
        ) {
            return Either::left('invalid length');
        }
        return Either::of(new Label(
            $name,
            $marginTop,
            $marginLeft,
            $width,
            $height,
            $intervalColomn,
            $intervalRow,
    
        ));
    }
    public static function nameConstraint(string $name): Either
    {
        return Either::fromNullable(filter_var(
            $name,
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9A-Za-z_-]{1,50}$/'
                ]
            ],
        ));
    }
    public function __get($prop)
    {
        return $this->$prop;
    }
}