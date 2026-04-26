<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * Creates the `auth_provider` table — operator-managed identity-provider
 * registrations (cf. ADR 0003).
 *
 * Multiple instances of the same `type` are allowed (e.g. two OIDC
 * tenants for staff and partners). The login screen renders one button
 * per `enabled = 1` row, ordered by `is_default DESC, name ASC`.
 *
 * Client secrets and SAML private keys live in `client_secret_encrypted`
 * as AES-256-GCM ciphertext produced by
 * `Saso\Infrastructure\Auth\Crypto\SecretEncryptor` (M3-E). Plaintext
 * never touches disk; the admin UI shows `●●●` and a "Replace" button.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateAuthProvider extends AbstractMigration
{
    public function up(): void
    {
        $this->table('auth_provider', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Pluggable IdP registrations (ADR 0003).',
        ])
            ->addColumn('name', 'string', [
                'limit'   => 100,
                'null'    => false,
                'comment' => 'Display name shown on the login button.',
            ])
            ->addColumn('type', 'enum', [
                'values'  => ['local', 'oidc', 'saml'],
                'null'    => false,
                'comment' => 'Discriminator — drives which AuthProvider implementation is constructed.',
            ])
            ->addColumn('issuer_or_metadata_url', 'string', [
                'limit'   => 500,
                'null'    => true,
                'comment' => 'OIDC discovery URL (.well-known/openid-configuration) or SAML metadata URL.',
            ])
            ->addColumn('client_id', 'string', [
                'limit' => 255,
                'null'  => true,
            ])
            ->addColumn('client_secret_encrypted', 'blob', [
                'limit'   => MysqlAdapter::BLOB_REGULAR,
                'null'    => true,
                'comment' => 'AES-256-GCM ciphertext from SecretEncryptor; APP_KEY is the wrapping key.',
            ])
            ->addColumn('scopes', 'string', [
                'limit'   => 500,
                'null'    => true,
                'comment' => 'Space-separated scope list for OIDC; ignored for SAML.',
            ])
            ->addColumn('claim_mapping', 'json', [
                'null'    => true,
                'comment' => 'Operator override for IdP claim names (cf. Saso\\Domain\\Auth\\ClaimMapping).',
            ])
            ->addColumn('enabled', 'boolean', [
                'null'    => false,
                'default' => 0,
            ])
            ->addColumn('is_default', 'boolean', [
                'null'    => false,
                'default' => 0,
                'comment' => '1 = featured first on the login screen.',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['type', 'enabled'], ['name' => 'idx_type_enabled'])
            ->create();
    }

    public function down(): void
    {
        $this->table('auth_provider')->drop()->update();
    }
}
