<?php
namespace saso\repository\member;

use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    public function getQuery(): string
    {
        return 'INSERT INTO Member (id, userName, password) VALUES (:id, :userName, :password)';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
        $stmt->bindValue(':userName', $input['userName']);
        $stmt->bindValue(':password', $input['password']);
    }
    public function map(): \Closure
    {
        return fn() => null;
    }
}
