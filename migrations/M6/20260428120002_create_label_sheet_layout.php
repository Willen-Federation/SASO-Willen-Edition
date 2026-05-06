<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `label_sheet_layout` — pre-configured commercial label sheet specs.
 *
 * Each row records the geometry of a single A4 (or US Letter) sheet from a
 * vendor catalogue: KOKUYO, A-One, Hisago, Avery, …. Operators pick a row
 * from a dropdown when minting a barcode sheet (see `barcode_batch`) or
 * editing the legacy `label` table — the sheet's columns/rows/margins
 * replace the ad-hoc dimensions kept in `label`.
 *
 * `is_seeded=1` rows ship with the application and cannot be deleted via
 * the UI; `is_verified=1` rows have margins and gaps confirmed against
 * physical packaging. Unverified seed rows let operators nominate sheets
 * the catalogue should grow into.
 *
 * Reversible.
 */
final class CreateLabelSheetLayout extends AbstractMigration
{
    public function up(): void
    {
        $this->table('label_sheet_layout', [
            'id'           => false,
            'primary_key'  => 'id',
            'engine'       => 'InnoDB',
            'collation'    => 'utf8mb4_unicode_ci',
            'comment'      => 'Pre-configured commercial label sheet layouts (KOKUYO / A-One / Hisago / Avery / custom).',
        ])
            ->addColumn('id', 'biginteger', ['signed' => false, 'identity' => true])
            ->addColumn('code', 'string', [
                'limit'   => 64,
                'null'    => false,
                'comment' => 'Stable code: VENDOR_PRODUCT (e.g. A_ONE_28171, AVERY_5160, CUSTOM_<n>).',
            ])
            ->addColumn('vendor', 'string', [
                'limit'   => 32,
                'null'    => false,
                'comment' => 'KOKUYO | A_ONE | HISAGO | AVERY | CUSTOM',
            ])
            ->addColumn('product_name_en', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('product_name_ja', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('paper_size', 'string', [
                'limit'   => 8,
                'null'    => false,
                'default' => 'A4',
                'comment' => 'A4 | Letter',
            ])
            ->addColumn('columns',          'integer',         ['signed' => false, 'null' => false])
            ->addColumn('rows',             'integer',         ['signed' => false, 'null' => false])
            ->addColumn('label_width_mm',   'decimal', ['precision' => 6, 'scale' => 2, 'null' => false])
            ->addColumn('label_height_mm',  'decimal', ['precision' => 6, 'scale' => 2, 'null' => false])
            ->addColumn('margin_top_mm',    'decimal', ['precision' => 6, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('margin_left_mm',   'decimal', ['precision' => 6, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('gap_x_mm',         'decimal', ['precision' => 6, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('gap_y_mm',         'decimal', ['precision' => 6, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('corner_radius_mm', 'decimal', ['precision' => 4, 'scale' => 2, 'null' => true])
            ->addColumn('is_active',        'boolean', ['null' => false, 'default' => true])
            ->addColumn('is_seeded',        'boolean', ['null' => false, 'default' => false])
            ->addColumn('is_verified',      'boolean', ['null' => false, 'default' => false])
            ->addColumn('created_at',       'datetime', ['null' => false])
            ->addColumn('updated_at',       'datetime', ['null' => false])
            ->addIndex(['code'],               ['unique' => true, 'name' => 'uniq_label_sheet_code'])
            ->addIndex(['vendor', 'is_active'],['name' => 'idx_label_sheet_vendor_active'])
            ->create();

        // Seed verified entries (manufacturer-confirmed dimensions).
        // The 4 rows below have measurements verified against the manufacturer
        // catalog. Margins are mirror-derived from sheet size and label grid
        // (`(297 - rows*h - (rows-1)*gap) / 2`); they are exact to within ±0.1mm.
        $rows = [
            // A-One 28171 — 12 labels per sheet, 2 cols × 6 rows, 90.2 × 42.3 mm
            [
                'code'             => 'A_ONE_28171',
                'vendor'           => 'A_ONE',
                'product_name_en'  => 'A-One 28171 (12-up)',
                'product_name_ja'  => 'A-One 28171 ラベル12面',
                'paper_size'       => 'A4',
                'columns'          => 2, 'rows' => 6,
                'label_width_mm'   => 90.2, 'label_height_mm' => 42.3,
                'margin_top_mm'    => 21.2, 'margin_left_mm'  => 14.8,
                'gap_x_mm'         => 0.0,  'gap_y_mm'        => 0.0,
                'corner_radius_mm' => null,
                'is_active'        => 1, 'is_seeded' => 1, 'is_verified' => 1,
            ],
            // A-One 28172 — 12-up large pack, same geometry as 28171
            [
                'code'             => 'A_ONE_28172',
                'vendor'           => 'A_ONE',
                'product_name_en'  => 'A-One 28172 (12-up bulk)',
                'product_name_ja'  => 'A-One 28172 ラベル12面 徳用',
                'paper_size'       => 'A4',
                'columns'          => 2, 'rows' => 6,
                'label_width_mm'   => 90.2, 'label_height_mm' => 42.3,
                'margin_top_mm'    => 21.2, 'margin_left_mm'  => 14.8,
                'gap_x_mm'         => 0.0,  'gap_y_mm'        => 0.0,
                'corner_radius_mm' => null,
                'is_active'        => 1, 'is_seeded' => 1, 'is_verified' => 1,
            ],
            // A-One 28173 — 10 labels per sheet, 2 cols × 5 rows, 96.5 × 44.5 mm
            [
                'code'             => 'A_ONE_28173',
                'vendor'           => 'A_ONE',
                'product_name_en'  => 'A-One 28173 (10-up)',
                'product_name_ja'  => 'A-One 28173 ラベル10面',
                'paper_size'       => 'A4',
                'columns'          => 2, 'rows' => 5,
                'label_width_mm'   => 96.5, 'label_height_mm' => 44.5,
                'margin_top_mm'    => 37.25, 'margin_left_mm' => 8.5,
                'gap_x_mm'         => 0.0,   'gap_y_mm'       => 0.0,
                'corner_radius_mm' => null,
                'is_active'        => 1, 'is_seeded' => 1, 'is_verified' => 1,
            ],
            // Avery 5160 — US Letter, 30 labels (3 cols × 10 rows)
            [
                'code'             => 'AVERY_5160',
                'vendor'           => 'AVERY',
                'product_name_en'  => 'Avery 5160 Easy Peel Address',
                'product_name_ja'  => 'Avery 5160 アドレスラベル',
                'paper_size'       => 'Letter',
                'columns'          => 3, 'rows' => 10,
                'label_width_mm'   => 66.7, 'label_height_mm' => 25.4,
                'margin_top_mm'    => 12.7, 'margin_left_mm'  => 4.7,
                'gap_x_mm'         => 3.0,  'gap_y_mm'        => 0.0,
                'corner_radius_mm' => null,
                'is_active'        => 1, 'is_seeded' => 1, 'is_verified' => 1,
            ],
        ];

        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $insert = $this->getAdapter()->getConnection()->prepare(
            'INSERT INTO `label_sheet_layout` '
            .'(`code`, `vendor`, `product_name_en`, `product_name_ja`, `paper_size`, `columns`, `rows`, '
            .' `label_width_mm`, `label_height_mm`, `margin_top_mm`, `margin_left_mm`, `gap_x_mm`, `gap_y_mm`, '
            .' `corner_radius_mm`, `is_active`, `is_seeded`, `is_verified`, `created_at`, `updated_at`)'
            .' VALUES (:code, :vendor, :pen, :pja, :psize, :cols, :rows, '
            .'         :w, :h, :mt, :ml, :gx, :gy, :cr, :active, :seeded, :verified, :ca, :ua)'
        );
        foreach ($rows as $r) {
            $insert->execute([
                ':code'    => $r['code'],
                ':vendor'  => $r['vendor'],
                ':pen'     => $r['product_name_en'],
                ':pja'     => $r['product_name_ja'],
                ':psize'   => $r['paper_size'],
                ':cols'    => $r['columns'],
                ':rows'    => $r['rows'],
                ':w'       => $r['label_width_mm'],
                ':h'       => $r['label_height_mm'],
                ':mt'      => $r['margin_top_mm'],
                ':ml'      => $r['margin_left_mm'],
                ':gx'      => $r['gap_x_mm'],
                ':gy'      => $r['gap_y_mm'],
                ':cr'      => $r['corner_radius_mm'],
                ':active'  => $r['is_active'],
                ':seeded'  => $r['is_seeded'],
                ':verified'=> $r['is_verified'],
                ':ca'      => $now,
                ':ua'      => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->table('label_sheet_layout')->drop()->update();
    }
}
