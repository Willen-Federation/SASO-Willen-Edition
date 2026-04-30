<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates `item_draft` — the draft queue for AI-assisted item registration.
 *
 * State machine: queued → processing → ready | failed → confirmed | discarded
 * `processing_started_at` lets a watchdog promote stale `processing` rows
 * back to `queued` after a configurable timeout.
 */
final class CreateItemDraftTable extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('item_draft')) {
            return;
        }

        $this->table('item_draft', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Draft items awaiting AI enrichment and human confirmation.',
        ])
            ->addColumn('image_path', 'string', [
                'limit'   => 500,
                'null'    => false,
                'comment' => 'Server-relative path to uploaded image.',
            ])
            ->addColumn('barcode_hint', 'string', [
                'limit'   => 50,
                'null'    => true,
                'comment' => 'Pre-scanned JAN / ISBN / internal barcode supplied by user.',
            ])
            ->addColumn('user_data', 'json', [
                'null'    => true,
                'comment' => 'Fields explicitly entered by user — never overwritten by AI.',
            ])
            ->addColumn('ai_result', 'json', [
                'null'    => true,
                'comment' => 'Merged result from ISBN/JAN lookups + AI vision.',
            ])
            ->addColumn('status', 'enum', [
                'values'  => ['queued', 'processing', 'ready', 'failed', 'confirmed', 'discarded'],
                'null'    => false,
                'default' => 'queued',
                'comment' => 'State machine: queued→processing→ready|failed→confirmed|discarded',
            ])
            ->addColumn('processing_started_at', 'datetime', [
                'null'    => true,
                'comment' => 'Set when worker picks up; watchdog requeues if stale > 5 min.',
            ])
            ->addColumn('error_detail', 'string', [
                'limit' => 500,
                'null'  => true,
            ])
            ->addColumn('created_by', 'integer', [
                'null'   => true,
                'signed' => false,
            ])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['status', 'created_at'], ['name' => 'idx_draft_status_created'])
            ->addIndex(['created_by'],            ['name' => 'idx_draft_created_by'])
            ->create();
    }

    public function down(): void
    {
        $this->table('item_draft')->drop()->update();
    }
}
