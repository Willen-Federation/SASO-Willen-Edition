<?php
namespace saso\repository\role;

use saso\repository\DbPrepare;

final class Update implements DbPrepare
{
    public function getQuery(): string
    {
        return 'UPDATE Role SET label = :label, permissions = :permissions WHERE name = :name';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':name', $input['name']);
        $stmt->bindValue(':label', $input['label']);
        $stmt->bindValue(':permissions', $input['permissions']);
    }
    public function map(): \Closure
    {
        return fn() => null;
    }
}
