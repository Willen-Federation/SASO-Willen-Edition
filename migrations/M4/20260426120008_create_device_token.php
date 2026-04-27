<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `device_token` table — long-lived Flutter device credentials.
 *
 * Issued after a successful pairing-code exchange. The Flutter app presents
 * the raw token as `Authorization: Bearer <token>` on every API call. The DB
 * stores only the SHA-256 hash; if the DB is compromised, tokens cannot be
 * replayed without the matching raw value that was returned once at issuance.
 *
 * `revoked = 1` means an admin has explicitly invalidated the token. Both
 * `revoked = 1` and `expires_at < NOW()` are treated as authentication
 * failures by the middleware.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateDeviceToken extends AbstractMigration
{
    public function up(): void
    {
        $this->table('device_token', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Long-lived API credentials for paired Flutter devices.',
        ])
            ->addColumn('token_hash', 'string', [
                'limit'   => 64,
                'null'    => false,
                'comment' => 'SHA-256 hex digest of the raw URL-safe base64 device token.',
            ])
            ->addColumn('device_name', 'string', [
                'limit'   => 200,
                'null'    => false,
                'comment' => 'Human-readable device name supplied by the Flutter app at connect time.',
            ])
            ->addColumn('revoked', 'boolean', [
                'null'    => false,
                'default' => 0,
                'comment' => '1 = explicitly revoked by an admin.',
            ])
            ->addColumn('last_used_at', 'datetime', [
                'null'    => true,
                'comment' => 'UTC timestamp of the most recent authenticated request.',
            ])
            ->addColumn('expires_at', 'datetime', [
                'null'    => false,
                'comment' => 'UTC expiry (default: one year from issuance).',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['token_hash'], ['unique' => true, 'name' => 'uniq_device_token_hash'])
            ->addIndex(['expires_at'], ['name' => 'idx_device_expires_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('device_token')->drop()->update();
    }
}
