<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `member_external_identity` table — links a SASO `Member`
 * row to one or more external identities (OIDC `sub`, SAML `NameID`)
 * provided by a registered `auth_provider` (cf. ADR 0003).
 *
 * A member may have multiple identities (e.g. linked to both Entra ID
 * and Google Workspace). The pair `(auth_provider_id, external_subject)`
 * resolves to exactly one member, and a member is unique within a
 * given provider — both invariants are enforced as composite primary
 * key + secondary unique index.
 *
 * No FK constraints on `member_id` here: the legacy `Member` table
 * is named `Member` (PascalCase) and lives outside the M4 schema
 * boundary; the M4-G physical migration introduces the foreign keys
 * once the bounded context moves into `src/`.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateMemberExternalIdentity extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('member_external_identity')) {
            // Table was created by a failed previous run; ensure FK is intact.
            $this->execute(
                'ALTER TABLE `member_external_identity`
                 MODIFY COLUMN `auth_provider_id` INT UNSIGNED NOT NULL'
            );
            if (!$this->getAdapter()->hasForeignKey('member_external_identity', ['auth_provider_id'])) {
                $this->table('member_external_identity')
                    ->addForeignKey('auth_provider_id', 'auth_provider', 'id',
                        ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                    ->update();
            }
            return;
        }
        $this->table('member_external_identity', [
            'id'          => false,
            'primary_key' => ['auth_provider_id', 'external_subject'],
            'engine'      => 'InnoDB',
            'collation'   => 'utf8mb4_unicode_ci',
            'comment'     => 'Member ↔ external IdP identity links (ADR 0003).',
        ])
            ->addColumn('member_id', 'biginteger', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('auth_provider_id', 'integer', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('external_subject', 'string', [
                'limit'   => 255,
                'null'    => false,
                'comment' => 'OIDC sub claim or SAML NameID — opaque to SASO; stable for the IdP.',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('last_login_at', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['member_id', 'auth_provider_id'], [
                'unique' => true,
                'name'   => 'uniq_member_provider',
            ])
            ->addForeignKey(
                'auth_provider_id',
                'auth_provider',
                'id',
                ['delete' => 'CASCADE', 'update' => 'NO_ACTION'],
            )
            ->create();
    }

    public function down(): void
    {
        $this->table('member_external_identity')->drop()->update();
    }
}
