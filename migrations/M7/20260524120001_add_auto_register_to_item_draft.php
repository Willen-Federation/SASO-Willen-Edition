<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds auto_register / promoted_item_id columns to `item_draft`.
 *
 * `auto_register` (default 0) marks drafts that should be promoted directly
 * to an `item` row after enrichment finishes, skipping the manual
 * confirmation step.
 *
 * `promoted_item_id` records the resulting `item.id` and acts as the
 * idempotency guard for worker retries — `PromoteDraftToItemService`
 * checks this column under FOR UPDATE before issuing a second INSERT.
 */
final class AddAutoRegisterToItemDraft extends AbstractMigration
{
    public function up(): void
    {
        $this->table('item_draft')
            ->addColumn('auto_register', 'boolean', [
                'null'    => false,
                'default' => 0,
                'after'   => 'status',
                'comment' => 'When 1, ProcessItemDraftHandler promotes the draft directly to an item row after enrichment.',
            ])
            ->addColumn('promoted_item_id', 'integer', [
                'null'    => true,
                'default' => null,
                'signed'  => false,
                'after'   => 'auto_register',
                'comment' => 'item.id created from this draft. Set once; serves as idempotency guard for worker retries.',
            ])
            ->addIndex(['auto_register', 'status'], ['name' => 'idx_draft_auto_register_status'])
            ->save();
    }

    public function down(): void
    {
        $this->table('item_draft')
            ->removeIndex(['auto_register', 'status'])
            ->save();

        $this->table('item_draft')
            ->removeColumn('auto_register')
            ->removeColumn('promoted_item_id')
            ->save();
    }
}
