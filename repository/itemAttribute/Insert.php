<?php
namespace saso\repository\itemAttribute;

use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    public function __construct(
        private array $definition,
    ) {
    }

    public function getQuery(): string
    {
        return '
            INSERT INTO item_attribute_definition
                (code, label_ja, label_en, value_type, unit, required, enum_values,
                 validation_regex, sort_order, show_on_web, show_on_mobile, created_at, updated_at)
            VALUES
                (:code, :label_ja, :label_en, :value_type, :unit, :required, :enum_values,
                 :validation_regex, :sort_order, :show_on_web, :show_on_mobile, :created_at, :updated_at)
        ';
    }

    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':code',             $this->definition['code']);
        $stmt->bindValue(':label_ja',         $this->definition['label_ja']);
        $stmt->bindValue(':label_en',         $this->definition['label_en']);
        $stmt->bindValue(':value_type',       $this->definition['value_type']);
        $stmt->bindValue(':unit',             $this->definition['unit']);
        $stmt->bindValue(':required',         (int) $this->definition['required'], \PDO::PARAM_INT);
        $stmt->bindValue(':enum_values',      $this->definition['enum_values']);
        $stmt->bindValue(':validation_regex', $this->definition['validation_regex']);
        $stmt->bindValue(':sort_order',       (int) $this->definition['sort_order'], \PDO::PARAM_INT);
        $stmt->bindValue(':show_on_web',      (int) $this->definition['show_on_web'], \PDO::PARAM_INT);
        $stmt->bindValue(':show_on_mobile',   (int) $this->definition['show_on_mobile'], \PDO::PARAM_INT);
        $stmt->bindValue(':created_at',       $input['now']);
        $stmt->bindValue(':updated_at',       $input['now']);
    }

    public function map(): \Closure
    {
        return fn() => true;
    }
}
