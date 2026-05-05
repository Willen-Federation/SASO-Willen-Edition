<?php
namespace saso\mypage;

use saso\repository\DbPrepare;

final class EditProfileUpdateRepository implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            UPDATE Member
            SET display_name = :display_name,
                bio = :bio,
                avatar_url = :avatar_url,
                updated_at = :updated_at
            WHERE id = :id
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', $input['id']);
        $stmt->bindValue(':display_name', $input['display_name']);
        $stmt->bindValue(':bio', $input['bio']);
        $stmt->bindValue(':avatar_url', $input['avatar_url']);
        $stmt->bindValue(':updated_at', $input['updated_at']);
    }

    public function map(): \Closure
    {
        return fn() => null;
    }
}
