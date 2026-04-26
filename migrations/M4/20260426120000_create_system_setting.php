<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * Creates the `system_setting` table — UI-editable runtime preferences
 * (cf. ADR 0006).
 *
 * Reads go through `Saso\Domain\Setting\SystemSettingService`; writes
 * happen from the admin Web UI and always emit a row in
 * `system_setting_audit` (next migration). Secrets that must live in DB
 * are encrypted with `Saso\Infrastructure\Auth\Crypto\SecretEncryptor`
 * (M3-E) using `APP_KEY`; UI never re-emits the plaintext.
 *
 * `key` is a reserved word in MySQL/MariaDB; we use the historical
 * SASO column name with backticks via Phinx's quoted-identifier output.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateSystemSetting extends AbstractMigration
{
    public function up(): void
    {
        $this->table('system_setting', [
            'id'          => false,
            'primary_key' => ['key'],
            'engine'      => 'InnoDB',
            'collation'   => 'utf8mb4_unicode_ci',
            'comment'     => 'Runtime preferences editable from the admin Web UI (ADR 0006).',
        ])
            ->addColumn('key', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Stable identifier, e.g. default_locale, mail.smtp_host.',
            ])
            ->addColumn('value', 'blob', [
                'limit'   => MysqlAdapter::BLOB_REGULAR,
                'null'    => false,
                'comment' => 'Raw value (encrypted ciphertext when value_type=secret).',
            ])
            ->addColumn('value_type', 'enum', [
                'values'  => ['string', 'int', 'bool', 'json', 'secret'],
                'null'    => false,
                'comment' => 'Drives parsing: string-as-utf8, int via (int), bool via 0/1, json via json_decode, secret via SecretEncryptor.',
            ])
            ->addColumn('encrypted', 'boolean', [
                'null'    => false,
                'default' => 0,
                'comment' => '1 when the value column carries SecretEncryptor ciphertext.',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_by', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Member id of the admin who set this row, or "installer" for bootstrap rows.',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('system_setting')->drop()->update();
    }
}
