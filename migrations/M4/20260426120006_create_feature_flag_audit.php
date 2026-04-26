<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `feature_flag_audit` table — append-only history of
 * every flag toggle (cf. ADR 0005).
 *
 * `changed_by` is either a member id (operator-driven flip from the
 * admin UI) or the literal string `circuit_breaker` when the cron
 * sweep auto-disabled the flag. Post-mortems use `flag_key` +
 * `changed_at` to reconstruct what happened.
 *
 * `flag_key` is denormalised here on purpose: when a flag row is
 * deleted, the audit history must remain decodable.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateFeatureFlagAudit extends AbstractMigration
{
    public function up(): void
    {
        $this->table('feature_flag_audit', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Append-only audit log for feature_flag writes (ADR 0005).',
        ])
            ->addColumn('flag_key', 'string', [
                'limit' => 120,
                'null'  => false,
            ])
            ->addColumn('old_enabled', 'boolean', [
                'null' => false,
            ])
            ->addColumn('new_enabled', 'boolean', [
                'null' => false,
            ])
            ->addColumn('changed_by', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Member id, "circuit_breaker", or "installer" for bootstrap flips.',
            ])
            ->addColumn('changed_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('reason', 'string', [
                'limit' => 500,
                'null'  => true,
            ])
            ->addIndex(['flag_key', 'changed_at'], ['name' => 'idx_flag_changed'])
            ->create();
    }

    public function down(): void
    {
        $this->table('feature_flag_audit')->drop()->update();
    }
}
