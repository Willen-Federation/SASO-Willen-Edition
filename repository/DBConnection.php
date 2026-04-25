<?php

namespace saso\repository;

use saso\ConfigLoader;

final class DBConnection
{
    private static $pdo;
    private static function create(string $dsn, ?string $user, ?string $password): void
    {
        try{
            self::$pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_CLASS
            ]);
        } catch(\PDOException $e) {
            die($e->getMessage());
        }
    }
    public static function getPdo(): \PDO
    {
        if(self::$pdo instanceof \PDO) return self::$pdo;
        $config = ConfigLoader::load()['database'];
        self::create(...$config);
        return self::$pdo;
    }
    public static function pdo(): \PDO
    {
        return self::getPdo();
    }
}
