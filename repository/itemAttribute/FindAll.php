<?php
namespace saso\repository\itemAttribute;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAll implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT id, code, label_ja, label_en, value_type, unit, required,
                   enum_values, sort_order, show_on_web, show_on_mobile
            FROM item_attribute_definition
            ORDER BY sort_order ASC, id ASC
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
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
            'enum_values'  => $v->enum_values !== null ? (string) $v->enum_values : null,
            'sort_order'   => (int) $v->sort_order,
            'show_on_web'  => (bool) $v->show_on_web,
            'show_on_mobile' => (bool) $v->show_on_mobile,
        ]);
    }
}
