<?php
namespace saso\repository\member;

use saso\entity;
use saso\repository\DbPrepare;
use saso\util\Each;

/**
 * Look up a Member row by its login id only.
 *
 * Pre-M1 this also matched a precomputed password digest in the WHERE clause,
 * which forced deterministic per-user hashes. Since M1 the password column
 * stores an Argon2id digest and verification happens in PHP via
 * Member::verifyPassword(), so this query returns the stored hash for the
 * caller to validate.
 */
final class FindOneByAuth implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT id, password, userName, role FROM Member
                WHERE id = :id
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new entity\Member(
            $v->id,
            $v->userName,
            $v->password,
            $v->role ?? 'operator',
        ));
    }
}
