<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `storage_location` table — hierarchical, code-bearing
 * locations operators print on shelves and scan with barcode readers
 * (cf. ADR 0011).
 *
 * `code` is operator-readable and barcode-friendly
 * (`<warehouse-prefix>-<row>-<col>-<bin>`). It is unique across the
 * tree so a reprinted label always resolves to exactly one row.
 *
 * `parent_id` builds the tree (nullable for roots); `depth` is
 * denormalised so the admin UI can render typeahead without a CTE on
 * every keystroke.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateStorageLocation extends AbstractMigration
{
    public function up(): void
    {
        $this->table('storage_location', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Hierarchical storage locations with deterministic codes (ADR 0011).',
        ])
            ->addColumn('parent_id', 'biginteger', [
                'signed'  => false,
                'null'    => true,
                'comment' => 'Self-FK; NULL for root nodes.',
            ])
            ->addColumn('code', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Stable, deterministic, barcode-friendly code unique across the tree.',
            ])
            ->addColumn('name', 'string', [
                'limit'   => 200,
                'null'    => false,
                'comment' => 'Human-readable label shown in the admin UI.',
            ])
            ->addColumn('position', 'integer', [
                'null'    => false,
                'default' => 0,
                'comment' => 'Sibling sort order under the parent.',
            ])
            ->addColumn('depth', 'integer', [
                'limit'   => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'signed'  => false,
                'null'    => false,
                'comment' => 'Denormalised tree depth — 0 = root.',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['code'], ['unique' => true, 'name' => 'uniq_code'])
            ->addIndex(['parent_id', 'position'], ['name' => 'idx_parent_position'])
            ->addForeignKey(
                'parent_id',
                'storage_location',
                'id',
                ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'],
            )
            ->create();
    }

    public function down(): void
    {
        $this->table('storage_location')->drop()->update();
    }
}
