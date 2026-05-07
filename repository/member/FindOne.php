<?php

namespace saso\repository\member;

use saso\entity;
use saso\repository\DBConnection;
use saso\repository\DbPrepare;
use saso\util\Each;

/**
 * Look up a Member row by its id, including profile fields shown on My Page.
 */
final class FindOne implements DbPrepare
{
    private static ?bool $hasProfileColumns = null;

    public function getQuery(): string
    {
        if (self::profileColumnsExist()) {
            return '
                SELECT id, password, userName, role,
                       avatar_url,
                       display_name,
                       bio,
                       updated_at
                    FROM Member
                    WHERE id = :id
            ';
        }

        return '
            SELECT id, password, userName, role,
                   avatar_url,
                   display_name,
                   bio,
                   updated_at
                FROM Member
                WHERE id = :id
        ';
    }

    private static function profileColumnsExist(): bool
    {
        if (self::$hasProfileColumns !== null) {
            return self::$hasProfileColumns;
        }

        try {
            $stmt = DBConnection::pdo()->query("SHOW COLUMNS FROM Member LIKE 'display_name'");
            self::$hasProfileColumns = $stmt !== false && $stmt->fetch(\PDO::FETCH_ASSOC) !== false;
        } catch (\Throwable) {
            self::$hasProfileColumns = false;
        }

        return self::$hasProfileColumns;
    }

    /** @param array<string, mixed> $input */
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
    }

    public function map(): \Closure
    {
        return Each::tf(function ($v) {
            $updatedAt = null;
            if (!empty($v->updated_at)) {
                $updatedAt = new \DateTime($v->updated_at);
            }
            return new entity\Member(
                $v->id,
                $v->userName,
                $v->password,
                $v->role ?? 'operator',
                $v->avatar_url ?? null,
                $v->display_name ?? null,
                $v->bio ?? null,
                $updatedAt,
            );
        });
    }
}
