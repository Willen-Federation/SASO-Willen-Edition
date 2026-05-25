<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNotificationLog extends AbstractMigration
{
    public function up(): void
    {
        $this->table('notification_log', ['id' => true, 'primary_key' => 'id'])
            ->addColumn('title',            'string',   ['limit' => 255, 'null' => false])
            ->addColumn('body',             'text',     ['null' => false])
            ->addColumn('target',           'string',   ['limit' => 512, 'null' => false])
            ->addColumn('target_type',      'string',   ['limit' => 16,  'null' => false, 'default' => 'topic'])
            ->addColumn('image_url',        'string',   ['limit' => 512, 'null' => true,  'default' => null])
            ->addColumn('sent_at',          'datetime', ['null' => false])
            ->addColumn('sent_by',          'string',   ['limit' => 255, 'null' => false])
            ->addColumn('success',          'boolean',  ['null' => false, 'default' => false])
            ->addColumn('fcm_message_name', 'string',   ['limit' => 512, 'null' => true,  'default' => null])
            ->addColumn('error_message',    'text',     ['null' => true,  'default' => null])
            ->addIndex(['sent_at'])
            ->create();

        $now = date('Y-m-d H:i:s');
        $this->table('system_setting')
            ->insert([
                [
                    'key'        => 'firebase.service_account_key',
                    'value'      => '',
                    'value_type' => 'secret',
                    'encrypted'  => 0,
                    'updated_at' => $now,
                    'updated_by' => 'system',
                ],
            ])
            ->saveData();
    }

    public function down(): void
    {
        $this->table('notification_log')->drop()->save();
        $this->execute("DELETE FROM system_setting WHERE `key` = 'firebase.service_account_key'");
    }
}
