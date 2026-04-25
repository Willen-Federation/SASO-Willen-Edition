<?php
namespace saso\repository;

interface DbPrepare
{
    public function getQuery(): string;
    public function bind(\PDOStatement $stmt, array $input): void;
    public function map(): \Closure;
}
