<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds lifecycle status and storage-location assignment to the legacy
 * `item` table (cf. ADR 0014, M6-I MCP tools).
 *
 * `status` drives the `set_item_status` MCP tool (active / archived /
 * discontinued / pending). `storage_location_id` drives the
 * `assign_item_location` MCP tool; SET NULL on location delete keeps
 * items intact while clearing their placement.
 *
 * Reversible: `down()` drops both columns and the FK.
 */
final class AddItemStatusAndLocation extends AbstractMigration
{
    public function up(): void
    {
        $this->table('Item')
            ->addColumn('status', 'string', [
                'limit'   => 32,
                'null'    => false,
                'default' => 'active',
                'comment' => 'Lifecycle status: active | archived | discontinued | pending.',
            ])
            ->addColumn('storage_location_id', 'integer', [
                'null'    => true,
                'signed'  => false,
                'comment' => 'FK → storage_location.id; null = unassigned.',
            ])
            ->addIndex(['status'], ['name' => 'idx_item_status'])
            ->addIndex(['storage_location_id'], ['name' => 'idx_item_location'])
            ->addForeignKey('storage_location_id', 'storage_location', 'id', [
                'delete'     => 'SET_NULL',
                'update'     => 'NO_ACTION',
                'constraint' => 'fk_item_storage_location',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('Item')
            ->dropForeignKey('storage_location_id')
            ->removeIndex(['storage_location_id'])
            ->removeIndex(['status'])
            ->removeColumn('storage_location_id')
            ->removeColumn('status')
            ->update();
    }
}
