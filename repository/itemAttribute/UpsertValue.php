<?php
namespace saso\repository\itemAttribute;

use saso\repository\DbPrepare;

final class UpsertValue implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            INSERT INTO item_attribute_value
                (item_id, attribute_code, value_string, value_int, value_float, value_bool,
                 created_at, updated_at)
            VALUES
                (:item_id, :attribute_code, :value_string, :value_int, :value_float, :value_bool,
                 :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                value_string = VALUES(value_string),
                value_int    = VALUES(value_int),
                value_float  = VALUES(value_float),
                value_bool   = VALUES(value_bool),
                updated_at   = VALUES(updated_at)
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':item_id',        (int) $input['item_id'], \PDO::PARAM_INT);
        $stmt->bindValue(':attribute_code', $input['attribute_code']);
        $stmt->bindValue(':value_string',   $input['value_string']);
        $stmt->bindValue(':value_int',      $input['value_int'] !== null ? (int) $input['value_int'] : null,
                         $input['value_int'] !== null ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
        $stmt->bindValue(':value_float',    $input['value_float']);
        $stmt->bindValue(':value_bool',     $input['value_bool'] !== null ? (int) $input['value_bool'] : null,
                         $input['value_bool'] !== null ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
        $stmt->bindValue(':created_at',     $input['now']);
        $stmt->bindValue(':updated_at',     $input['now']);
    }

    public function map(): \Closure
    {
        return fn() => true;
    }
}
