<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWebauthnTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('webauthn_credential', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'biginteger', ['signed' => false, 'identity' => true])
            ->addColumn('member_id', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('credential_id', 'blob', ['null' => false])
            ->addColumn('public_key', 'blob', ['null' => false])
            ->addColumn('sign_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('transports', 'json', ['null' => true])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_used_at', 'datetime', ['null' => true])
            ->addIndex(['member_id'])
            ->addIndex(['credential_id'], ['unique' => true, 'limit' => 255])
            ->create();

        $this->table('webauthn_challenge', ['id' => false, 'primary_key' => 'challenge'])
            ->addColumn('challenge', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('member_id', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('purpose', 'enum', ['values' => ['registration', 'authentication']])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addIndex(['member_id'])
            ->addIndex(['expires_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('webauthn_challenge')->drop()->save();
        $this->table('webauthn_credential')->drop()->save();
    }
}
