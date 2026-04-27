<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Extends `storage_location` with physical-detail columns so that each
 * node can be typed and annotated precisely (cf. M6-I-006).
 *
 * location_type — Hierarchical role of the node:
 *   facility | zone | aisle | rack | shelf | tier | bin (default)
 *   (施設 | ゾーン | 通路・列 | ラック | 棚 | 段 | 棚区画)
 *
 * description   — Optional free-text description shown in the UI.
 * capacity      — Optional maximum number of items that can be stored here.
 * notes         — Operator freeform notes (e.g. temperature zone, hazard flags).
 *
 * All new columns are nullable / defaulted so existing rows remain valid
 * after the migration.
 *
 * Reversible: `down()` drops the four columns.
 */
final class AddStorageLocationDetail extends AbstractMigration
{
    public function up(): void
    {
        $this->table('storage_location')
            ->addColumn('location_type', 'string', [
                'limit'   => 32,
                'null'    => false,
                'default' => 'bin',
                'comment' => 'Node type: facility | zone | aisle | rack | shelf | tier | bin.',
            ])
            ->addColumn('description', 'text', [
                'null'    => true,
                'comment' => 'Optional free-text description of the location.',
            ])
            ->addColumn('capacity', 'integer', [
                'null'    => true,
                'signed'  => false,
                'comment' => 'Optional maximum item count this location can hold.',
            ])
            ->addColumn('notes', 'text', [
                'null'    => true,
                'comment' => 'Operator freeform notes (temperature zone, hazard flags, etc.).',
            ])
            ->addIndex(['location_type'], ['name' => 'idx_location_type'])
            ->update();
    }

    public function down(): void
    {
        $this->table('storage_location')
            ->removeIndex(['location_type'])
            ->removeColumn('notes')
            ->removeColumn('capacity')
            ->removeColumn('description')
            ->removeColumn('location_type')
            ->update();
    }
}
