<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds isbn column to the item table.
 *
 * ISBN-13 barcodes (978.../979... prefix) are distinct from JAN/EAN codes
 * and map to the Open Library ISBN lookup pipeline. Stored separately so
 * both a JAN product code and an ISBN can coexist on the same item (e.g.
 * a retail-packaged book might carry both).
 */
final class AddIsbnToItem extends AbstractMigration
{
    public function change(): void
    {
        $this->table('Item')
            ->addColumn('isbn', 'string', [
                'limit'   => 32,
                'null'    => true,
                'default' => null,
                'comment' => 'ISBN-13 barcode (978.../979... prefix). NULL when not a book.',
            ])
            ->addIndex(['isbn'], ['name' => 'idx_item_isbn'])
            ->update();
    }
}
