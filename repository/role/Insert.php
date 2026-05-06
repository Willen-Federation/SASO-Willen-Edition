<?php
namespace saso\repository\role;

use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    public function getQuery(): string
    {
        return 'INSERT INTO Role (name, label, permissions) VALUES (:name, :label, :permissions)';
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
