<?php
namespace saso\repository\item;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindLastSerialByDateCode implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT MAX(serial) AS max
                FROM Item
                WHERE dateCode = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['now']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>$v->max);
    }
}

