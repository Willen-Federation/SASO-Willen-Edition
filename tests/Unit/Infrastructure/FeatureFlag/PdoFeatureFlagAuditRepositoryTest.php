<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\FeatureFlag;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagAuditRepository;

final class PdoFeatureFlagAuditRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoFeatureFlagAuditRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        $this->repo = new PdoFeatureFlagAuditRepository($this->pdo);
    }

    public function testRecordPersistsRow(): void
    {
        $this->repo->record('checkout.new_flow', oldEnabled: true, newEnabled: false, changedBy: 'admin-1', reason: 'rollback');

        $list = $this->repo->listForFlag('checkout.new_flow');
        self::assertCount(1, $list);
        self::assertSame('checkout.new_flow', $list[0]->flagKey);
        self::assertTrue($list[0]->oldEnabled);
        self::assertFalse($list[0]->newEnabled);
        self::assertSame('admin-1', $list[0]->changedBy);
        self::assertSame('rollback', $list[0]->reason);
    }

    public function testListForFlagOrdersNewestFirst(): void
    {
        // Newer rows have a larger autoincrement id, so reverse order
        // by id is a stable proxy for changed_at DESC at sub-second
        // resolution.
        $this->repo->record('a', false, true, 'admin-1');
        usleep(10_000);
        $this->repo->record('a', true, false, 'admin-2');

        $list = $this->repo->listForFlag('a');
        self::assertCount(2, $list);
        self::assertSame('admin-2', $list[0]->changedBy);
        self::assertSame('admin-1', $list[1]->changedBy);
    }

    public function testListForFlagFiltersByKey(): void
    {
        $this->repo->record('a', false, true, 'x');
        $this->repo->record('b', false, true, 'x');

        self::assertCount(1, $this->repo->listForFlag('a'));
        self::assertCount(1, $this->repo->listForFlag('b'));
    }

    public function testListForFlagHonoursLimit(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->repo->record('a', false, true, 'x');
        }

        self::assertCount(2, $this->repo->listForFlag('a', limit: 2));
    }

    public function testCircuitBreakerEventDetection(): void
    {
        $this->repo->record('a', true, false, 'circuit_breaker', reason: 'errors > threshold');

        $entry = $this->repo->listForFlag('a')[0];
        self::assertTrue($entry->isCircuitBreakerEvent());
    }
}
