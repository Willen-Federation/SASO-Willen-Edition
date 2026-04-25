<?php
namespace saso\repository;

final class DBExecuter
{
    public static function write(\PDOStatement $stmt): void
    {
        $stmt->execute()?:throw new \Exception('データベースの更新に失敗しました');
    }
    public static function read(\PDOStatement $stmt): \PDOStatement
    {
        $stmt->execute()?:throw new \Exception('データベースの読込に失敗しました');
        $stmt->setFetchMode(\PDO::FETCH_OBJ);
        return $stmt;
    }
}
