<?php
namespace saso\repository\role;

use saso\entity\Role;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOne implements DbPrepare
{
    public function getQuery(): string
    {
        return 'SELECT name, label, permissions FROM Role WHERE name = :name LIMIT 1';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':name', $input['name']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v) => new Role(
            $v->name,
            $v->label,
            json_decode($v->permissions ?? '[]', true) ?: [],
        ));
    }
}
