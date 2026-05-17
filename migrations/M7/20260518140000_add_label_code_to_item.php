<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds label_code column to the item table.
 *
 * Custom label/shelf codes are distinct from JAN/EAN and ISBN barcodes and
 * are used for internal shelf or bin identifiers (e.g. warehouse location
 * labels, custom SKU stickers). Stored separately so a JAN code, ISBN, and
 * a custom label can all coexist on the same item.
 */
final class AddLabelCodeToItem extends AbstractMigration
{
    public function up(): void
    {
        $this->table('item')
            ->addColumn('label_code', 'string', [
                'limit'   => 64,
                'null'    => true,
                'default' => null,
                'after'   => 'isbn',
                'comment' => 'Custom label/shelf code. NULL when not set.',
            ])
            ->addIndex(['label_code'], ['name' => 'idx_item_label_code'])
            ->save();
    }

    public function down(): void
    {
        $this->table('item')
            ->removeIndex(['label_code'])
            ->save();

        $this->table('item')
            ->removeColumn('label_code')
            ->save();
    }
}
