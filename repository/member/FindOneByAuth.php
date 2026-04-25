<?php
namespace saso\repository\member;

use saso\entity;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneByAuth implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT id,userName FROM Member
                WHERE id = :id
                AND password = :password
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
        $stmt->bindValue(':password', $input['password']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new entity\Member(
            $v->id,
            $v->userName,
            '',
        ));
    }
}
