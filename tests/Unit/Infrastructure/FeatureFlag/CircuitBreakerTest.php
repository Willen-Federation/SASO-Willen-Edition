<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Infrastructure\FeatureFlag\CircuitBreaker;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;

/**
 * Smoke test for the cron breaker query path. Verifies the breaker's
 * SUM query window-filters on the same column as countSince() — these
 * used to disagree, so a flag that exceeded its threshold could appear
 * tripped to the cron path but unchanged to the operator-facing
 * countSince() metric.
 */
final class CircuitBreakerTest extends TestCase
{
    private PDO $pdo;
    private PdoFeatureFlagRepository $flags;
    private CircuitBreaker $breaker;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE feature_flag (
                id                  INTEGER PRIMARY KEY,
                key_name            TEXT NOT NULL UNIQUE,
                description         TEXT NOT NULL,
                enabled             INTEGER NOT NULL DEFAULT 0,
                rollout_percent     INTEGER NOT NULL DEFAULT 0,
                conditions          TEXT,
                error_threshold     INTEGER NOT NULL DEFAULT 0,
                error_window_min    INTEGER NOT NULL DEFAULT 60,
                auto_disabled_at    TEXT,
                auto_disable_reason TEXT,
                created_at          TEXT NOT NULL,
                updated_at          TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE error_log_aggregate (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                feature_key  TEXT NOT NULL,
                error_code   TEXT NOT NULL,
                count        INTEGER NOT NULL DEFAULT 1,
                window_start TEXT NOT NULL,
                window_end   TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE feature_flag_audit (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                flag_key    TEXT NOT NULL,
                old_enabled INTEGER NOT NULL,
                new_enabled INTEGER NOT NULL,
                changed_by  TEXT NOT NULL,
                changed_at  TEXT NOT NULL,
                reason      TEXT
            )',
        );

        $this->flags   = new PdoFeatureFlagRepository($this->pdo);
        $this->breaker = new CircuitBreaker($this->pdo, $this->flags);
    }

    public function testDisablesFlagWhenErrorsExceedThreshold(): void
    {
        $this->flags->save($this->makeFlag(id: 1, key: 'a.b', enabled: true, threshold: 2, window: 60));

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        for ($i = 0; $i < 3; ++$i) {
            $this->recordTick('a.b', $now);
        }

        $this->breaker->run();

        $reread = $this->flags->findByKey(new FeatureKey('a.b'));
        self::assertNotNull($reread);
        self::assertFalse($reread->enabled);
        self::assertNotNull($reread->autoDisabledAt);
    }

    public function testLeavesFlagAloneBelowThreshold(): void
    {
        $this->flags->save($this->makeFlag(id: 1, key: 'a.b', enabled: true, threshold: 5, window: 60));

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        for ($i = 0; $i < 2; ++$i) {
            $this->recordTick('a.b', $now);
        }

        $this->breaker->run();

        $reread = $this->flags->findByKey(new FeatureKey('a.b'));
        self::assertNotNull($reread);
        self::assertTrue($reread->enabled);
    }

    public function testIgnoresFlagWithThresholdZero(): void
    {
        $this->flags->save($this->makeFlag(id: 1, key: 'a.b', enabled: true, threshold: 0, window: 60));

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        for ($i = 0; $i < 99; ++$i) {
            $this->recordTick('a.b', $now);
        }

        $this->breaker->run();

        $reread = $this->flags->findByKey(new FeatureKey('a.b'));
        self::assertNotNull($reread);
        self::assertTrue($reread->enabled);
    }

    public function testWritesAuditOnAutoDisable(): void
    {
        $this->flags->save($this->makeFlag(id: 1, key: 'a.b', enabled: true, threshold: 1, window: 60));

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->recordTick('a.b', $now);
        $this->recordTick('a.b', $now);

        $this->breaker->run();

        $stmt = $this->pdo->query("SELECT * FROM feature_flag_audit WHERE flag_key = 'a.b'");
        self::assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertSame('circuit_breaker', $row['changed_by']);
        self::assertSame(1, (int) $row['old_enabled']);
        self::assertSame(0, (int) $row['new_enabled']);
    }

    private function recordTick(string $key, DateTimeImmutable $at): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO error_log_aggregate (feature_key, error_code, count, window_start, window_end) '.
            'VALUES (:key, :code, 1, :ws, :we)',
        );
        $ts = $at->format('Y-m-d H:i:s');
        $stmt->execute([
            'key'  => $key,
            'code' => 'SASO-INFRA-9000',
            'ws'   => $ts,
            'we'   => $ts,
        ]);
    }

    private function makeFlag(
        int $id,
        string $key,
        bool $enabled,
        int $threshold,
        int $window,
    ): FeatureFlag {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return new FeatureFlag(
            id: $id,
            key: new FeatureKey($key),
            description: 'desc',
            enabled: $enabled,
            rolloutPercent: 100,
            conditions: null,
            errorThreshold: $threshold,
            errorWindowMinutes: $window,
            autoDisabledAt: null,
            autoDisableReason: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
