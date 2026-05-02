<?php
namespace saso\repository\member;

use saso\repository\DbPrepare;

final class Delete implements DbPrepare
{
    public function getQuery(): string
    {
        return 'DELETE FROM Member WHERE id = :id';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
    }
    public function map(): \Closure
    {
        return fn() => null;
    }
}
