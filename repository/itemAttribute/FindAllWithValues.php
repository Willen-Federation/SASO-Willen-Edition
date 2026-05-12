<?php
namespace saso\repository\itemAttribute;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAllWithValues implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT d.id, d.code, d.label_ja, d.label_en, d.value_type, d.unit,
                   d.required, d.enum_values, d.sort_order,
                   v.value_string, v.value_int, v.value_float, v.value_bool
            FROM item_attribute_definition d
            LEFT JOIN item_attribute_value v
                ON v.attribute_code = d.code AND v.item_id = :item_id
            WHERE d.show_on_web = 1
            ORDER BY d.sort_order ASC, d.id ASC
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':item_id', (int) $input['item_id'], \PDO::PARAM_INT);
    }

    public function map(): \Closure
    {
        return Each::tf(fn($v) => [
            'id'           => (int) $v->id,
            'code'         => (string) $v->code,
            'label_ja'     => (string) $v->label_ja,
            'label_en'     => (string) $v->label_en,
            'value_type'   => (string) $v->value_type,
            'unit'         => $v->unit !== null ? (string) $v->unit : null,
            'required'     => (bool) $v->required,
            'enum_values'  => $v->enum_values !== null ? json_decode((string) $v->enum_values, true) : [],
            'value_string' => $v->value_string !== null ? (string) $v->value_string : null,
            'value_int'    => $v->value_int !== null ? (int) $v->value_int : null,
            'value_float'  => $v->value_float !== null ? (float) $v->value_float : null,
            'value_bool'   => $v->value_bool !== null ? (bool) $v->value_bool : null,
        ]);
    }
}
