<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Inserts default shelf dimension configuration into `system_setting`.
 *
 * Populates the `shelf.dimension.config` key with the 5-dimension preset:
 * 1. 区分コード (letter)
 * 2. グループ番号 (numeric)
 * 3. 棚番号 (numeric)
 * 4. 位置 (numeric)
 * 5. (reserved for future use)
 */
final class AddShelfDimensionConfig extends AbstractMigration
{
    public function up(): void
    {
        $config = json_encode([
            [
                'name' => '区分コード',
                'description' => '製品の分類用',
                'type' => 'letter',
                'position' => 1,
                'enabled' => true,
            ],
            [
                'name' => 'グループ番号',
                'description' => '',
                'type' => 'numeric',
                'position' => 2,
                'enabled' => true,
            ],
            [
                'name' => '棚番号',
                'description' => '',
                'type' => 'numeric',
                'position' => 3,
                'enabled' => true,
            ],
            [
                'name' => '位置',
                'description' => '',
                'type' => 'numeric',
                'position' => 4,
                'enabled' => true,
            ],
            [
                'name' => '',
                'description' => '',
                'type' => 'numeric',
                'position' => 5,
                'enabled' => false,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->table('system_setting')
            ->insert([
                [
                    'key' => 'shelf.dimension.config',
                    'value' => $config,
                    'value_type' => 'json',
                    'encrypted' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 'system',
                ],
            ])
            ->saveData();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM system_setting WHERE `key` = 'shelf.dimension.config'");
    }
}
