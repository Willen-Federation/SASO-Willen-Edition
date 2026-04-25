<?php
namespace saso\repository\feature;

use saso\entity\Feature;
use saso\repository\color;
use saso\repository\DbPrepare;
use saso\repository\Finder;
use saso\repository\size;
use saso\repository\item\FindOneById;
use saso\util\Each;

final class FindOneByFullcode implements DbPrepare
{
    private Feature $feature;
    public function __construct(private Finder $finder)
    {
    }
    public function getQuery(): string 
    {
        return '
            SELECT concatId
                FROM Item
                WHERE concatId = 0
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $this->feature = $this->finder->current(new FindOneById(), ['id'=>$input['item']])->flatMap(
            fn($v)=>$this->finder->current(new color\FindOneByCodeAndItem($v), [
                'code'=>$input['color']
            ])
        )->flatMap(
            fn($c)=>$this->finder->current(new size\FindOneByCodeAndItem($c->item), [
                'code'=>$input['size']
            ])->flatMap(
                fn($s)=>new Feature(
                    $s->item,
                    $c,
                    $s,
                )
            )
        )->getOrElse(false);
    }
    public function map(): \Closure
    {
        return fn($v)=>Each::t($this->feature);
    }
}