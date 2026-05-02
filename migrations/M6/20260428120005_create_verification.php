<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Data verification (照合) — `verification_session` and `verification_event`.
 *
 * Two operating modes (`session.mode`):
 *   - `stocktake`  full count under a scope (area_code / location id);
 *                  expected items are diffed against scanned items at
 *                  `complete()` time, producing one `missing` event per
 *                  expected item that was never scanned.
 *   - `spot`       single barcode → expected vs actual location compare.
 *
 * Reversible.
 */
final class CreateVerification extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('verification_session')) {
            $this->table('verification_session', [
                'comment' => 'Stocktake / spot-verification sessions (M6-J3 Phase 4).',
            ])
                ->addColumn('mode', 'string', [
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'stocktake | spot',
                ])
                ->addColumn('area_code', 'string', [
                    'limit' => 32,
                    'null'  => true,
                ])
                ->addColumn('scope_location_id', 'biginteger', [
                    'signed' => false,
                    'null'   => true,
                ])
                ->addColumn('started_by', 'string', [
                    'limit' => 64,
                    'null'  => true,
                ])
                ->addColumn('started_at',   'datetime', ['null' => false])
                ->addColumn('completed_at', 'datetime', ['null' => true])
                ->addColumn('status', 'string', [
                    'limit'   => 16,
                    'null'    => false,
                    'default' => 'active',
                    'comment' => 'active | completed | abandoned',
                ])
                ->addColumn('notes', 'text', ['null' => true])
                ->addIndex(['status', 'started_at'], ['name' => 'idx_verif_status_started'])
                ->addIndex(['area_code'],            ['name' => 'idx_verif_area'])
                ->create();
        }

        if (!$this->hasTable('verification_event')) {
            $this->table('verification_event', [
                'comment' => 'Per-scan event recorded against a verification_session.',
            ])
                ->addColumn('session_id',   'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('scanned_code', 'string',     ['limit' => 20, 'null' => false])
                ->addColumn('resolved_kind', 'string', [
                    'limit'   => 16,
                    'null'    => false,
                    'comment' => 'pending | feature | unknown',
                ])
                ->addColumn('resolved_item_id',     'string',     ['limit' => 64, 'null' => true])
                ->addColumn('expected_location_id', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('actual_location_id',   'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('result', 'string', [
                    'limit'   => 24,
                    'null'    => false,
                    'comment' => 'match | missing | unexpected | mismatch_location | unknown_code',
                ])
                ->addColumn('scanned_at', 'datetime', ['null' => false])
                ->addColumn('device_id',  'biginteger', ['signed' => false, 'null' => true])
                ->addIndex(['session_id', 'result'], ['name' => 'idx_verif_event_session_result'])
                ->addIndex(['scanned_code'],         ['name' => 'idx_verif_event_code'])
                ->addForeignKey('session_id', 'verification_session', 'id', [
                    'delete'      => 'CASCADE',
                    'update'      => 'NO_ACTION',
                    'constraint'  => 'fk_verif_event_session',
                ])
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('verification_event')->drop()->update();
        $this->table('verification_session')->drop()->update();
    }
}
