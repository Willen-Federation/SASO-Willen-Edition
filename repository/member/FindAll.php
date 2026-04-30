<?php
namespace saso\repository\member;

use saso\entity;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAll implements DbPrepare
{
    public function getQuery(): string
    {
        return 'SELECT id, password, userName, role FROM Member ORDER BY id ASC';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v) => new entity\Member(
            $v->id,
            $v->userName,
            $v->password,
            $v->role,
        ));
    }
}
