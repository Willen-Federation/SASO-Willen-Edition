<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `item_attribute_value` table — typed EAV values for
 * operator-defined item attributes (cf. ADR 0011, M6-E3).
 *
 * Each row stores one attribute value for one item. The `value_type`
 * of the attribute definition determines which column holds the value:
 *   - string / barcode / enum → value_string (TEXT)
 *   - int                    → value_int  (BIGINT)
 *   - float                  → value_float (DOUBLE)
 *   - bool                   → value_bool  (TINYINT 0/1)
 *
 * The composite PK (item_id, attribute_code) ensures at most one
 * value per attribute per item. CASCADE on attribute code update/delete
 * keeps the value table consistent when attribute definitions change.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateItemAttributeValue extends AbstractMigration
{
    public function up(): void
    {
        $this->table('item_attribute_value', [
            'id'          => false,
            'primary_key' => ['item_id', 'attribute_code'],
            'engine'      => 'InnoDB',
            'collation'   => 'utf8mb4_unicode_ci',
            'comment'     => 'Typed EAV attribute values per item (ADR 0011, M6-E3).',
        ])
            ->addColumn('item_id', 'biginteger', [
                'null'   => false,
                'signed' => false,
            ])
            ->addColumn('attribute_code', 'string', [
                'limit' => 120,
                'null'  => false,
            ])
            ->addColumn('value_string', 'text', [
                'null' => true,
            ])
            ->addColumn('value_int', 'biginteger', [
                'null' => true,
            ])
            ->addColumn('value_float', 'float', [
                'null' => true,
            ])
            ->addColumn('value_bool', 'boolean', [
                'null' => true,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['attribute_code'], ['name' => 'idx_iav_code'])
            ->addForeignKey('attribute_code', 'item_attribute_definition', 'code', [
                'delete'     => 'CASCADE',
                'update'     => 'CASCADE',
                'constraint' => 'fk_iav_definition',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('item_attribute_value')->drop()->update();
    }
}
