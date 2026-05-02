<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `location_map` — uploaded warehouse floor plans.
 *
 * Operators upload an image (JPEG/PNG/SVG, normalised by upload validator)
 * to `storage/maps/{uuid}.{ext}`. This row records the original pixel
 * dimensions so pins can be rendered at any later display size.
 *
 * Soft-deleted (`deleted_at`) rather than hard-deleted: pins on
 * `storage_location` survive the parent map's removal so the operator can
 * rebind them to a re-uploaded floor plan without losing the position.
 *
 * Reversible.
 */
final class CreateLocationMap extends AbstractMigration
{
    public function up(): void
    {
        $this->table('location_map', [
            'comment' => 'Uploaded warehouse floor-plan images for shelf pinning.',
        ])
            ->addColumn('name', 'string', [
                'limit' => 120,
                'null'  => false,
            ])
            ->addColumn('image_path', 'string', [
                'limit'   => 255,
                'null'    => false,
                'comment' => 'Path under storage/maps/ (uuid + extension).',
            ])
            ->addColumn('original_width_px', 'integer', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('original_height_px', 'integer', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('uploaded_by', 'string', [
                'limit' => 64,
                'null'  => true,
            ])
            ->addColumn('uploaded_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at',  'datetime', ['null' => true])
            ->addIndex(['deleted_at'], ['name' => 'idx_location_map_deleted'])
            ->create();
    }

    public function down(): void
    {
        $this->table('location_map')->drop()->update();
    }
}
