<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds `operational_status` to `storage_location`.
 *
 * Values: available | receiving | shipping | reserved |
 *         no_outbound | maintenance | full | closed
 *
 * Reversible: `down()` drops the column.
 */
final class AddStorageLocationStatus extends AbstractMigration
{
    public function up(): void
    {
        $this->table('storage_location')
            ->addColumn('operational_status', 'string', [
                'limit'   => 32,
                'null'    => false,
                'default' => 'available',
                'comment' => 'Operational state: available|receiving|shipping|reserved|no_outbound|maintenance|full|closed.',
            ])
            ->addIndex(['operational_status'], ['name' => 'idx_location_operational_status'])
            ->update();
    }

    public function down(): void
    {
        $this->table('storage_location')
            ->removeIndex(['operational_status'])
            ->removeColumn('operational_status')
            ->update();
    }
}
