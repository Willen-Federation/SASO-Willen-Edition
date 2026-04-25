<?php

namespace saso\repository;

interface TransactionInterface
{
    public function begin(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
}
