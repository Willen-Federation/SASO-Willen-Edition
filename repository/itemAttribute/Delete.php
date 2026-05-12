<?php
namespace saso\repository\itemAttribute;

use saso\repository\DbPrepare;

final class Delete implements DbPrepare
{
    public function getQuery(): string
    {
        return 'DELETE FROM item_attribute_definition WHERE id = :id';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':id', (int) $input['id'], \PDO::PARAM_INT);
    }

    public function map(): \Closure
    {
        return fn() => true;
    }
}
