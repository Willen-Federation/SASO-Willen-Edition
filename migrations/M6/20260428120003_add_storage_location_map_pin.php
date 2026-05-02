<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Extends `storage_location` with simple-shelf operator UX:
 *   - `area_code`        denormalised area string for fast filtering
 *   - `map_image_id`     FK location_map.id — optional floor-plan reference
 *   - `map_x_ratio`      0..1 normalised pin x position
 *   - `map_y_ratio`      0..1 normalised pin y position
 *
 * The `location_map` row is created by a separate migration (one file per
 * Phinx convention).
 *
 * Reversible.
 */
final class AddStorageLocationMapPin extends AbstractMigration
{
    public function up(): void
    {
        $this->table('storage_location')
            ->addColumn('area_code', 'string', [
                'limit'   => 32,
                'null'    => true,
                'after'   => 'code',
                'comment' => 'Denormalised area string used for fast filtering and the simple-shelf form.',
            ])
            ->addColumn('map_image_id', 'biginteger', [
                'signed'  => false,
                'null'    => true,
                'comment' => 'FK location_map.id — optional floor-plan reference.',
            ])
            ->addColumn('map_x_ratio', 'decimal', [
                'precision' => 6,
                'scale'     => 5,
                'null'      => true,
                'comment'   => '0.00000–1.00000 horizontal pin ratio.',
            ])
            ->addColumn('map_y_ratio', 'decimal', [
                'precision' => 6,
                'scale'     => 5,
                'null'      => true,
                'comment'   => '0.00000–1.00000 vertical pin ratio.',
            ])
            ->addIndex(['area_code'], ['name' => 'idx_storage_location_area'])
            ->update();
    }

    public function down(): void
    {
        $this->table('storage_location')
            ->removeIndexByName('idx_storage_location_area')
            ->removeColumn('area_code')
            ->removeColumn('map_image_id')
            ->removeColumn('map_x_ratio')
            ->removeColumn('map_y_ratio')
            ->update();
    }
}
