<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `item_attribute_definition` table — operator-defined
 * item attribute schema (cf. ADR 0011).
 *
 * Each row defines one attribute (size, weight, ISBN, country of
 * origin, …) with a typed `value_type`, optional unit, optional enum
 * value list, and an optional regex validator. The admin UI reads
 * the table to drive the item-edit form; values are stored typed in
 * `item_attribute_value` (next migration, ships with M6-E3).
 *
 * `code` is the canonical key (lowercase + alphanumeric + `_`/`.`)
 * — what the OpenSearch index uses, what plugins reference. The
 * label fields (`label_en` / `label_ja`) are operator-facing
 * presentation strings.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateItemAttributeDefinition extends AbstractMigration
{
    public function up(): void
    {
        $this->table('item_attribute_definition', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Operator-defined item attribute schema (ADR 0011).',
        ])
            ->addColumn('code', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Canonical key — lowercase alphanumeric + . / _.',
            ])
            ->addColumn('label_en', 'string', [
                'limit' => 200,
                'null'  => false,
            ])
            ->addColumn('label_ja', 'string', [
                'limit' => 200,
                'null'  => false,
            ])
            ->addColumn('value_type', 'enum', [
                'values'  => ['string', 'int', 'float', 'bool', 'enum', 'barcode'],
                'null'    => false,
                'comment' => 'Drives form widget + storage column on item_attribute_value.',
            ])
            ->addColumn('unit', 'string', [
                'limit'   => 40,
                'null'    => true,
                'comment' => 'Display unit ("kg", "cm", "mL"); ignored for non-numeric types.',
            ])
            ->addColumn('required', 'boolean', [
                'null'    => false,
                'default' => 0,
            ])
            ->addColumn('enum_values', 'json', [
                'null'    => true,
                'comment' => 'Enumerated allowed values, e.g. ["S","M","L"]; required when value_type = enum.',
            ])
            ->addColumn('validation_regex', 'string', [
                'limit'   => 500,
                'null'    => true,
                'comment' => 'Optional PCRE regex validator (without delimiters); applied client-side and server-side.',
            ])
            ->addColumn('sort_order', 'integer', [
                'null'    => false,
                'default' => 0,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['code'], ['unique' => true, 'name' => 'uniq_code'])
            ->addIndex(['sort_order'], ['name' => 'idx_sort'])
            ->create();
    }

    public function down(): void
    {
        $this->table('item_attribute_definition')->drop()->update();
    }
}
