<?php
namespace saso\repository\member;

use saso\repository\DbPrepare;

final class Update implements DbPrepare
{
    public function getQuery(): string
    {
        return 'UPDATE Member SET userName = :userName, role = :role WHERE id = :id';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
        $stmt->bindValue(':userName', $input['userName']);
        $stmt->bindValue(':role', $input['role']);
    }
    public function map(): \Closure
    {
        return fn() => null;
    }
}
