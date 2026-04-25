<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindLastId implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT MAX(categoryId) AS max
                FROM Category
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>$v->max);
    }
}
