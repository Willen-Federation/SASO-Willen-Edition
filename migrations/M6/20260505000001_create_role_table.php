<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the Role table for custom RBAC.
 *
 * Each row defines a named role with a human-readable label and a JSON array
 * of permission keys.  The default built-in roles (admin / operator) are
 * seeded here so existing Member rows keep working without any UPDATE.
 *
 * Permission keys correspond to the "matter" names in flow.json:
 *   item, category, label, shelf, barcode, verify, archive, scanStock,
 *   search, member, settingAdmin, featureAdmin, authExt, admin
 *
 * Member.role remains a plain VARCHAR — no FK — so rows created before this
 * migration is run are never orphaned.
 */
final class CreateRoleTable extends AbstractMigration
{
    private const ALL_PERMISSIONS = [
        'item', 'category', 'label', 'shelf', 'barcode',
        'verify', 'archive', 'scanStock', 'search',
        'member', 'settingAdmin', 'featureAdmin', 'authExt', 'admin',
    ];

    private const OPERATOR_PERMISSIONS = [
        'item', 'category', 'label', 'shelf', 'barcode',
        'verify', 'archive', 'scanStock', 'search',
    ];

    public function up(): void
    {
        $this->table('Role', ['id' => false, 'primary_key' => ['name']])
            ->addColumn('name', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('label', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('permissions', 'text', [
                'null'    => false,
                'default' => '[]',
                'comment' => 'JSON array of permission keys',
            ])
            ->create();

        $this->execute(sprintf(
            "INSERT INTO Role (name, label, permissions) VALUES ('admin', '管理者', '%s')",
            json_encode(self::ALL_PERMISSIONS, JSON_UNESCAPED_UNICODE)
        ));
        $this->execute(sprintf(
            "INSERT INTO Role (name, label, permissions) VALUES ('operator', 'オペレーター', '%s')",
            json_encode(self::OPERATOR_PERMISSIONS, JSON_UNESCAPED_UNICODE)
        ));
    }

    public function down(): void
    {
        $this->table('Role')->drop()->save();
    }
}
