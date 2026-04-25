<?php

namespace saso\repository;

final class DbTransaction implements TransactionInterface
{
    private $pdo;
    public function __construct()
    {
        $this->pdo = DBConnection::getPdo();
    }
    public function begin(): bool
    {
        return $this->pdo->beginTransaction();
    }
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
