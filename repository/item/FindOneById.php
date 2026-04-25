<?php
namespace saso\repository\item;

use saso\entity\Item;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneById implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT serial, itemName, pla, plaNote, paper, paperNote, createAt
                FROM Item
                WHERE concatId = :id
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Item(
            $v->serial,
            $v->itemName,
            $v->pla,
            $v->plaNote,
            $v->paper,
            $v->paperNote,
            new \DateTime($v->createAt),
        ));
    }
}
