<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindRangeById implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT categoryId, categoryLeft, categoryRight
                FROM Category
            WHERE categoryId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['id']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>[
            'id'=>$v->categoryId,
            'left'=>$v->categoryLeft,
            'right'=>$v->categoryRight,
        ]);
    }
}
