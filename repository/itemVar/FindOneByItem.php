<?php
namespace saso\repository\itemVar;

use saso\entity\Item;
use saso\entity\ItemVar;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneByItem implements DbPrepare
{
    public function __construct(
        private Item $item,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT concatId, categoryId, price, updateAt
                FROM Item
                WHERE concatId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->item->id);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new ItemVar(
            $this->item,
            $v->categoryId,
            $v->price,
            new \DateTime($v->updateAt),
        ));
    }
}
