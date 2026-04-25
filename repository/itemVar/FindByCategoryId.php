<?php
namespace saso\repository\itemVar;

use saso\entity\Item;
use saso\entity\ItemVar;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindByCategoryId implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM Item
                WHERE categoryId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['categoryId']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new ItemVar(
            new Item(
                $v->serial,
                $v->itemName,
                $v->pla,
                $v->plaNote,
                $v->paper,
                $v->paperNote,
                new \DateTime($v->createAt),
            ),
            $v->categoryId,
            $v->price,
            new \DateTime($v->updateAt),
        ));
    }
}
