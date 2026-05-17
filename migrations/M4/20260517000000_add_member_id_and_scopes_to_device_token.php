<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds `member_id` and `scopes` to `device_token`.
 *
 * Binds each issued device token to the admin Member who minted it
 * through the QR pairing flow, and stores the OAuth2-style scopes
 * granted at issuance time. Together these let the JWT carry a real
 * principal (`member_id`) and let the MCP server enforce per-tool
 * scope checks instead of granting blanket write access.
 *
 * Both columns are nullable so rows created before this migration
 * continue to exist. New rows always carry a non-null `member_id`
 * (gated by `requireSessionAuth()` on the pairing endpoint) and a
 * non-null `scopes` payload.
 *
 * Reversible: `down()` drops both columns.
 */
final class AddMemberIdAndScopesToDeviceToken extends AbstractMigration
{
    public function up(): void
    {
        $this->table('device_token')
            ->addColumn('member_id', 'string', [
                'limit'   => 20,
                'null'    => true,
                'after'   => 'refresh_token_hash',
                'comment' => 'Member.id of the admin who minted this device token via the pairing flow (null for legacy rows).',
            ])
            ->addColumn('scopes', 'text', [
                'null'    => true,
                'after'   => 'member_id',
                'comment' => 'JSON-encoded list of OAuth2-style scopes granted to this device (null for legacy rows; treated as empty).',
            ])
            ->addIndex(['member_id'], ['name' => 'idx_device_member_id'])
            ->update();
    }

    public function down(): void
    {
        $this->table('device_token')
            ->removeIndexByName('idx_device_member_id')
            ->removeColumn('member_id')
            ->removeColumn('scopes')
            ->update();
    }
}
