<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds `member_id` to `pairing_code`.
 *
 * Binds each pairing code to the admin Member who minted it through
 * the session-authenticated `POST /api/v1/mobile/pairing-codes`
 * endpoint. The member ID is carried forward to the `device_token`
 * created when the QR is scanned, so the resulting JWT can claim a
 * real principal.
 *
 * Nullable so legacy rows (created before the mobile-pairing
 * hardening) continue to exist; new rows always carry a value.
 *
 * Reversible: `down()` drops the column.
 */
final class AddMemberIdToPairingCode extends AbstractMigration
{
    public function up(): void
    {
        $this->table('pairing_code')
            ->addColumn('member_id', 'string', [
                'limit'   => 20,
                'null'    => true,
                'after'   => 'label',
                'comment' => 'Member.id of the admin who minted this pairing code (null for legacy rows).',
            ])
            ->addIndex(['member_id'], ['name' => 'idx_pairing_member_id'])
            ->update();
    }

    public function down(): void
    {
        $this->table('pairing_code')
            ->removeIndexByName('idx_pairing_member_id')
            ->removeColumn('member_id')
            ->update();
    }
}
