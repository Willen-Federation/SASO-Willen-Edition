<?php
namespace saso\repository\role;

use saso\repository\DbPrepare;

final class Delete implements DbPrepare
{
    public function getQuery(): string
    {
        return 'DELETE FROM Role WHERE name = :name';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':name', $input['name']);
    }
    public function map(): \Closure
    {
        return fn() => null;
    }
}
