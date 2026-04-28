<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `barcode_pool` — pre-minted barcodes printed on a sheet ahead of item
 * registration. Each row carries one CODE128A-encodable code with the format
 * `PND` + 9 zero-padded digits (12 chars total). When an operator scans the
 * label later, the system looks up this row, opens an item-registration form
 * pre-filled with the code, and on submit transitions `status` from `pending`
 * to `linked`. `voided` is terminal — for damaged labels.
 *
 * Reversible.
 */
final class CreateBarcodePool extends AbstractMigration
{
    public function up(): void
    {
        $this->table('barcode_pool', [
            'comment' => 'Pre-minted barcode codes; lifecycle pending → linked|voided.',
        ])
            ->addColumn('code', 'string', [
                'limit'   => 20,
                'null'    => false,
                'comment' => 'PND + 9-digit sequence (12 chars), unique across the table.',
            ])
            ->addColumn('status', 'string', [
                'limit'   => 16,
                'null'    => false,
                'default' => 'pending',
                'comment' => 'pending | linked | voided',
            ])
            ->addColumn('batch_id', 'biginteger', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('linked_item_id', 'string', [
                'limit'   => 64,
                'null'    => true,
                'comment' => 'Item.id — set when status transitions to linked.',
            ])
            ->addColumn('linked_at', 'datetime', ['null' => true])
            ->addColumn('linked_by_device_id', 'biginteger', [
                'signed' => false,
                'null'   => true,
            ])
            ->addColumn('voided_at', 'datetime', ['null' => true])
            ->addColumn('void_reason', 'string', [
                'limit' => 160,
                'null'  => true,
            ])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['code'],                      ['unique' => true, 'name' => 'uniq_barcode_pool_code'])
            ->addIndex(['status', 'batch_id'],        ['name' => 'idx_pool_status_batch'])
            ->addIndex(['linked_item_id'],            ['name' => 'idx_pool_linked_item'])
            ->addForeignKey('batch_id', 'barcode_batch', 'id', [
                'delete'  => 'NO_ACTION',
                'update'  => 'NO_ACTION',
                'constraint' => 'fk_pool_batch',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('barcode_pool')->drop()->update();
    }
}
