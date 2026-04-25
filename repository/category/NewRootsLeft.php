<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;
use saso\util\Each;

final class NewRootsLeft implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT MAX(categoryRight) AS max
                FROM Category
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>$v->max+1);
    }
}
