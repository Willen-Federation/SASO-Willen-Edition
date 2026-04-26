<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureKey;
use Saso\Infrastructure\FeatureFlag\PdoErrorLogAggregateRepository;

final class PdoErrorLogAggregateRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoErrorLogAggregateRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        $this->repo = new PdoErrorLogAggregateRepository($this->pdo);
    }

    public function testCountSinceReturnsZeroWhenEmpty(): void
    {
        self::assertSame(0, $this->repo->countSince(new FeatureKey('a.b'), new DateTimeImmutable('-1 hour')));
    }

    public function testRecordErrorThenCountSince(): void
    {
        $key = new FeatureKey('checkout.new_flow');
        $this->repo->recordError($key, 'SASO-INFRA-9000', new DateTimeImmutable('2026-04-26 12:00:00'));
        $this->repo->recordError($key, 'SASO-INFRA-9000', new DateTimeImmutable('2026-04-26 12:30:00'));
        $this->repo->recordError($key, 'SASO-AUTH-1001', new DateTimeImmutable('2026-04-26 12:45:00'));

        $count = $this->repo->countSince($key, new DateTimeImmutable('2026-04-26 11:00:00'));
        self::assertSame(3, $count);
    }

    public function testCountSinceFiltersByWindow(): void
    {
        $key = new FeatureKey('a.b');
        $this->repo->recordError($key, 'X', new DateTimeImmutable('2026-04-26 10:00:00'));
        $this->repo->recordError($key, 'X', new DateTimeImmutable('2026-04-26 12:00:00'));

        // Only the second tick should be in window.
        $count = $this->repo->countSince($key, new DateTimeImmutable('2026-04-26 11:00:00'));
        self::assertSame(1, $count);
    }

    public function testCountSinceFiltersByFeatureKey(): void
    {
        $a = new FeatureKey('a.b');
        $b = new FeatureKey('c.d');
        $this->repo->recordError($a, 'X', new DateTimeImmutable('2026-04-26 12:00:00'));
        $this->repo->recordError($b, 'X', new DateTimeImmutable('2026-04-26 12:00:00'));

        self::assertSame(1, $this->repo->countSince($a, new DateTimeImmutable('2026-04-26 11:00:00')));
        self::assertSame(1, $this->repo->countSince($b, new DateTimeImmutable('2026-04-26 11:00:00')));
    }

    public function testPurgeOlderThanReturnsRowCount(): void
    {
        $key = new FeatureKey('a.b');
        $this->repo->recordError($key, 'X', new DateTimeImmutable('2026-04-26 09:00:00'));
        $this->repo->recordError($key, 'X', new DateTimeImmutable('2026-04-26 10:00:00'));
        $this->repo->recordError($key, 'X', new DateTimeImmutable('2026-04-26 12:00:00'));

        $purged = $this->repo->purgeOlderThan(new DateTimeImmutable('2026-04-26 11:00:00'));

        self::assertSame(2, $purged);
        self::assertSame(1, $this->repo->countSince($key, new DateTimeImmutable('2026-04-26 00:00:00')));
    }
}
