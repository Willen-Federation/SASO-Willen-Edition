<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `barcode_batch` — pending-barcode mint batches.
 *
 * Each row groups N pending barcode codes that were minted together for one
 * label sheet. The label sheet layout (M6 `label_sheet_layout`) governs the
 * physical print; this row records WHO minted them, WHEN, and HOW MANY.
 *
 * Reversible.
 */
final class CreateBarcodeBatch extends AbstractMigration
{
    public function up(): void
    {
        $this->table('barcode_batch', [
            'id'           => false,
            'primary_key'  => 'id',
            'comment'      => 'Pending-barcode mint batches; printed first, attached to items later.',
        ])
            ->addColumn('id', 'biginteger', [
                'signed'   => false,
                'null'     => false,
                'identity' => true,
            ])
            ->addColumn('code', 'string', [
                'limit'   => 40,
                'null'    => false,
                'comment' => 'Operator-friendly batch slug (e.g. PND-20260428-001).',
            ])
            ->addColumn('label_sheet_layout_id', 'biginteger', [
                'signed'  => false,
                'null'    => true,
                'comment' => 'FK label_sheet_layout.id — sheet template used for this print run.',
            ])
            ->addColumn('requested_count', 'integer', [
                'signed'  => false,
                'null'    => false,
                'default' => 0,
            ])
            ->addColumn('created_count', 'integer', [
                'signed'  => false,
                'null'    => false,
                'default' => 0,
            ])
            ->addColumn('created_by', 'string', [
                'limit'   => 64,
                'null'    => true,
                'comment' => 'Member.id when minted via web; null when minted via MCP device token.',
            ])
            ->addColumn('created_via', 'string', [
                'limit'   => 16,
                'null'    => false,
                'default' => 'web',
                'comment' => 'web | mcp',
            ])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['code'],       ['unique' => true, 'name' => 'uniq_barcode_batch_code'])
            ->addIndex(['created_at'], ['name' => 'idx_barcode_batch_created_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('barcode_batch')->drop()->update();
    }
}
