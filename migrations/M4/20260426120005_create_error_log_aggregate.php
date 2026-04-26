<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `error_log_aggregate` table — per-flag error counts in
 * time buckets (cf. ADR 0005). Written from the global exception
 * handler (M3-B) when `ProblemExceptionHandler` can attribute the
 * failure to a feature; read by the cron circuit breaker
 * (`scripts/feature_flag_circuit_breaker.php`, M4-E2).
 *
 * `feature_key` mirrors `feature_flag.key_name` but is **not** an FK —
 * the writer is a high-traffic synchronous path and we do not want a
 * stale flag delete to block error logging. The breaker does its own
 * existence check before flipping anything.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateErrorLogAggregate extends AbstractMigration
{
    public function up(): void
    {
        $this->table('error_log_aggregate', [
            'id'        => 'id',
            'engine'    => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment'   => 'Per-flag error rates in time buckets, fed by ProblemExceptionHandler (ADR 0005).',
        ])
            ->addColumn('feature_key', 'string', [
                'limit' => 120,
                'null'  => false,
            ])
            ->addColumn('error_code', 'string', [
                'limit'   => 40,
                'null'    => false,
                'comment' => 'SASO-DOMAIN-NNNN — the same identifier the response carries.',
            ])
            ->addColumn('count', 'integer', [
                'signed'  => false,
                'null'    => false,
                'default' => 1,
            ])
            ->addColumn('window_start', 'datetime', [
                'null' => false,
            ])
            ->addColumn('window_end', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['feature_key', 'window_start'], ['name' => 'idx_feature_window'])
            ->create();
    }

    public function down(): void
    {
        $this->table('error_log_aggregate')->drop()->update();
    }
}
