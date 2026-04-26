<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `plugin_registry` table — lifecycle ledger for
 * Composer-discovered plugins (cf. ADR 0015).
 *
 * Each row carries the plugin's Composer package name (unique), the
 * fully-qualified class that implements `Saso\Domain\Plugin\Plugin`,
 * the plugin's display name and version as reported in
 * `composer.json`, plus `activated_at` / `deactivated_at` / `last_seen_at`
 * timestamps so operators can answer "what plugins are running?" with
 * a single SQL query.
 *
 * `settings_json` carries per-plugin configuration the
 * `SystemSettingService` writes to (encrypted secrets stay in
 * `system_setting` proper; this column is for non-secret per-plugin
 * preferences).
 *
 * Reversible: `down()` drops the table.
 */
final class CreatePluginRegistry extends AbstractMigration
{
    public function up(): void
    {
        $this->table('plugin_registry', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Composer-discovered plugin lifecycle ledger (ADR 0015).',
        ])
            ->addColumn('package', 'string', [
                'limit'   => 200,
                'null'    => false,
                'comment' => 'Composer package name, e.g. willen-federation/saso-plugin-foo.',
            ])
            ->addColumn('class', 'string', [
                'limit'   => 200,
                'null'    => false,
                'comment' => 'FQCN of the Plugin implementation declared in extra.saso.plugin.class.',
            ])
            ->addColumn('name', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Human-readable plugin name shown in the admin UI.',
            ])
            ->addColumn('version', 'string', [
                'limit'   => 40,
                'null'    => false,
                'comment' => 'Version reported in the plugin package composer.json.',
            ])
            ->addColumn('activated_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('deactivated_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('last_seen_at', 'datetime', [
                'null'    => true,
                'comment' => 'Most recent boot at which Plugin::register() succeeded.',
            ])
            ->addColumn('settings_json', 'json', [
                'null' => true,
            ])
            ->addIndex(['package'], ['unique' => true, 'name' => 'uniq_package'])
            ->addIndex(['deactivated_at'], ['name' => 'idx_active'])
            ->create();
    }

    public function down(): void
    {
        $this->table('plugin_registry')->drop()->update();
    }
}
