<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * Creates the `system_setting_audit` table — append-only history of
 * every UI-driven change to a `system_setting` row (cf. ADR 0006).
 *
 * Old / new values are stored as the same blob shape the live row
 * uses. For `value_type=secret` rows that means the ciphertext; the
 * plaintext is never copied here.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateSystemSettingAudit extends AbstractMigration
{
    public function up(): void
    {
        $this->table('system_setting_audit', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Append-only audit log for system_setting writes (ADR 0006).',
        ])
            ->addColumn('key', 'string', [
                'limit' => 120,
                'null'  => false,
            ])
            ->addColumn('old_value', 'blob', [
                'limit' => MysqlAdapter::BLOB_REGULAR,
                'null'  => true,
            ])
            ->addColumn('new_value', 'blob', [
                'limit' => MysqlAdapter::BLOB_REGULAR,
                'null'  => true,
            ])
            ->addColumn('changed_by', 'string', [
                'limit' => 120,
                'null'  => false,
            ])
            ->addColumn('changed_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('reason', 'string', [
                'limit' => 500,
                'null'  => true,
            ])
            ->addIndex(['key', 'changed_at'], ['name' => 'idx_key_changed'])
            ->create();
    }

    public function down(): void
    {
        $this->table('system_setting_audit')->drop()->update();
    }
}
