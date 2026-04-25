<?php
namespace saso\entity;

use saso\util\monad\Either;

final class ItemVar
{
    public function __construct(
        private Item $item,
        private ?int $categoryId,
        private ?int $price,
        private \DateTime $updateAt,
    )
    {
    }
    public function __get($name)
    {
        return $this->$name;
    }
    /** @return Either<int> */
    public static function priceConstraint(string $price): Either
    {
        return Either::of($price)
            ->filter(fn($v)=>mb_strlen($v)<=11)
            ->flatMap(fn($v)=>preg_replace('/\,/', '', $v))
            ->filter(fn($v)=>mb_strlen($v)<=9)
            ->filter(fn($v)=>preg_match('/[^\d]/', $v)===0)
            ->flatMap(fn($v)=>(int)$v);
    }
}
