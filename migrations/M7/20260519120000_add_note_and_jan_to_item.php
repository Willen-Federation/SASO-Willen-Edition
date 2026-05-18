<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds note and jan_code columns to the legacy Item table.
 *
 * `note` carries free-form remarks beyond the packaging-specific plaNote /
 * paperNote columns — anything the operator wants to record about an item
 * (handling instructions, supplier comments, internal labelling notes).
 *
 * `jan_code` stores a JAN/EAN product barcode. ISBN already lives in its
 * own column (see 20260518120000_add_isbn_to_item.php); a JAN code and an
 * ISBN can coexist on the same item (e.g. a retail-packaged book).
 */
final class AddNoteAndJanToItem extends AbstractMigration
{
    public function up(): void
    {
        $this->table('Item')
            ->addColumn('note', 'string', [
                'limit'   => 255,
                'null'    => true,
                'default' => null,
                'comment' => 'Free-form remarks. NULL when not set.',
            ])
            ->addColumn('jan_code', 'string', [
                'limit'   => 32,
                'null'    => true,
                'default' => null,
                'comment' => 'JAN/EAN product barcode. NULL when not set.',
            ])
            ->addIndex(['jan_code'], ['name' => 'idx_item_jan_code'])
            ->update();
    }

    public function down(): void
    {
        $this->table('Item')
            ->removeIndex(['jan_code'])
            ->removeColumn('jan_code')
            ->removeColumn('note')
            ->update();
    }
}
