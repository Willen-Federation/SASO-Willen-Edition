<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;

final class DeleteWithChildren implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            DELETE FROM Category
                WHERE categoryLeft BETWEEN :categoryLeft
                                       AND :categoryRight
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':categoryLeft', $input['left']);
        $stmt->bindValue(':categoryRight', $input['right']);
    }
    public function map(): \Closure
    {
        return fn()=>null;
    }
}
