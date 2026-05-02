<?php

declare(strict_types=1);

use Saso\Infrastructure\Migration\Migration;

/**
 * M6: Create idempotency_key table for safe retries on POST/PATCH endpoints.
 *
 * The mobile client sends an Idempotency-Key UUID with every mutation.
 * The server stores the response body for 24 h so that retries of the
 * same operation return the original result without re-executing it.
 */
final class Migration20260502000000 extends Migration
{
    public function up(): void
    {
        $this->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS idempotency_key (
              `key`          VARCHAR(255) NOT NULL,
              response_json  MEDIUMTEXT   NOT NULL,
              created_at     DATETIME     NOT NULL,
              PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );
    }

    public function down(): void
    {
        $this->exec('DROP TABLE IF EXISTS idempotency_key');
    }
}
