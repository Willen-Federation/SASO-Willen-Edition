<?php
namespace saso\entity;

use saso\util\monad\Either;

final class Feature
{
    private ?Shelf $shelf=null;
    private ?int $labelAmount=null;

    public function __construct(
        private Item $item,
        private Color $color,
        private Size $size,
    )
    {
    }
    public static function validFeaturesAmount(int $featuresAmount): bool 
    {
        return $featuresAmount <= 100;
    }
    public function setShelf(?Shelf $shelf): void
    {
        $this->shelf = $shelf;
    }
    public function setLabelAmount(?LabelCache $labelCache): void
    {
        $this->labelAmount = $labelCache?->amount;
    }
    public function __get($name)
    {
        return $this->$name;
    }
    public function getCode(): string
    {
        return $this->color->code.$this->size->code;
    }
    public function getFullCode(): string
    {
        return $this->item->id.$this->getCode();
    }
    public static function substrFullCode(string $fullCode): array
    {
        return [
            'item'=>substr($fullCode, 0, 8),
            'color'=>substr($fullCode, 8, 2),
            'size'=>substr($fullCode, 10, 2),
        ];
    }
    public static function fullCodeConstraint(string $fullCode): Either
    {
        return Either::fromNullable(filter_var(
            $fullCode,
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^\d{12}$/'
                ]
            ]
        ));
    }
    public static function validateCode(int $code): Either
    {
        return Either::of($code)
            ->filter(fn($v)=>$v>=0&&$v<100)
            ->map(fn($v)=>sprintf('%02d',$v));
    }
    public static function codeConstraint(string $code): Either
    {
        return Either::fromNullable(filter_var(
            $code,
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^\d{2}$/'
                ]
            ],
        ));
    }
}
