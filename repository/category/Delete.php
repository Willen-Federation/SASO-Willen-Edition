<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;

final class Delete implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            DELETE FROM Category
                WHERE categoryId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['id']);
    }
    public function map(): \Closure
    {
        return fn()=>null;
    }
}
