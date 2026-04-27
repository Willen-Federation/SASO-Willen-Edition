<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds `refresh_token_hash` to `device_token`.
 *
 * The previous design used the device token itself as a long-lived
 * bearer credential. After switching to JWT-based authentication the
 * device token row becomes the refresh-token record: the short-lived
 * JWT access token is stateless (not stored), and the opaque refresh
 * token is stored here as a SHA-256 hash.
 *
 * The column is nullable so that rows created before this migration
 * (or via the previous code path) continue to exist without error.
 * In practice all new rows will have a non-null value.
 *
 * Reversible: `down()` drops the column.
 */
final class AddRefreshTokenHashToDeviceToken extends AbstractMigration
{
    public function up(): void
    {
        $this->table('device_token')
            ->addColumn('refresh_token_hash', 'string', [
                'limit'   => 64,
                'null'    => true,
                'after'   => 'token_hash',
                'comment' => 'SHA-256 hex digest of the long-lived opaque refresh token (null for legacy rows).',
            ])
            ->addIndex(['refresh_token_hash'], ['unique' => true, 'name' => 'uniq_refresh_token_hash'])
            ->update();
    }

    public function down(): void
    {
        $this->table('device_token')
            ->removeColumn('refresh_token_hash')
            ->update();
    }
}
