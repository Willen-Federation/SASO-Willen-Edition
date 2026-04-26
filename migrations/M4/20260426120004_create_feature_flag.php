<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `feature_flag` table — operator-managed runtime
 * switches with built-in circuit-breaker metadata (cf. ADR 0005).
 *
 * `key_name` is the application-level identifier (`checkout.new_flow`,
 * `auth.oidc.discovery_cache`, …) that call sites pass into the
 * OpenFeature client. `error_threshold` + `error_window_min` declare
 * the circuit-breaker policy: when the cron sweep observes more than
 * `error_threshold` failures attributed to this flag in the last
 * `error_window_min` minutes, it sets `enabled = 0` and writes the
 * audit row. `error_threshold = 0` means "never auto-disable".
 *
 * Reversible: `down()` drops the table.
 */
final class CreateFeatureFlag extends AbstractMigration
{
    public function up(): void
    {
        $this->table('feature_flag', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Operator-managed feature flags with circuit-breaker policy (ADR 0005).',
        ])
            ->addColumn('key_name', 'string', [
                'limit'   => 120,
                'null'    => false,
                'comment' => 'Application identifier passed to OpenFeature client.getXValue().',
            ])
            ->addColumn('description', 'string', [
                'limit' => 500,
                'null'  => false,
            ])
            ->addColumn('enabled', 'boolean', [
                'null'    => false,
                'default' => 0,
            ])
            ->addColumn('rollout_percent', 'integer', [
                'limit'   => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'signed'  => false,
                'null'    => false,
                'default' => 0,
                'comment' => '0-100; only honoured when enabled = 1.',
            ])
            ->addColumn('conditions', 'json', [
                'null'    => true,
                'comment' => 'Targeting rules — JSON shape evaluated by the OpenFeature provider.',
            ])
            ->addColumn('error_threshold', 'integer', [
                'signed'  => false,
                'null'    => false,
                'default' => 0,
                'comment' => '0 = never auto-disable; otherwise the cron breaker disables the flag once aggregate errors over `error_window_min` exceed this count.',
            ])
            ->addColumn('error_window_min', 'integer', [
                'signed'  => false,
                'null'    => false,
                'default' => 60,
                'comment' => 'Sliding window (minutes) the breaker uses to count errors.',
            ])
            ->addColumn('auto_disabled_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('auto_disable_reason', 'string', [
                'limit' => 500,
                'null'  => true,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['key_name'], ['unique' => true, 'name' => 'uniq_key_name'])
            ->create();
    }

    public function down(): void
    {
        $this->table('feature_flag')->drop()->update();
    }
}
