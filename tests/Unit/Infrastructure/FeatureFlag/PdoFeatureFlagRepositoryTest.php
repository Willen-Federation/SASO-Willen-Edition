<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;

final class PdoFeatureFlagRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoFeatureFlagRepository $repo;

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

        $this->repo = new PdoFeatureFlagRepository($this->pdo);
    }

    public function testFindByKeyReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findByKey(new FeatureKey('nope')));
    }

    public function testSaveThenFindByKeyRoundTrips(): void
    {
        $flag = $this->makeFlag(id: 1, key: 'a.b', enabled: true, rollout: 25);
        $this->repo->save($flag);

        $reread = $this->repo->findByKey(new FeatureKey('a.b'));
        self::assertNotNull($reread);
        self::assertSame(1, $reread->id);
        self::assertTrue($reread->enabled);
        self::assertSame(25, $reread->rolloutPercent);
    }

    public function testSaveUpdatesExistingRow(): void
    {
        $this->repo->save($this->makeFlag(id: 1, enabled: false));
        $this->repo->save($this->makeFlag(id: 1, enabled: true));

        $reread = $this->repo->findById(1);
        self::assertNotNull($reread);
        self::assertTrue($reread->enabled);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM feature_flag');
        self::assertInstanceOf(\PDOStatement::class, $stmt);
        self::assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testConditionsRoundTripsAsJson(): void
    {
        $this->repo->save($this->makeFlag(id: 1, conditions: ['country' => 'jp', 'role' => 'admin']));

        $reread = $this->repo->findById(1);
        self::assertNotNull($reread);
        self::assertSame(['country' => 'jp', 'role' => 'admin'], $reread->conditions);
    }

    public function testTripBreakerStateRoundTrips(): void
    {
        $now  = new DateTimeImmutable('2026-04-26 13:00:00');
        $flag = $this->makeFlag(id: 1, enabled: true, threshold: 50);
        $this->repo->save($flag);

        $tripped = $this->repo->save($flag->tripBreaker($now, 'errors exceeded threshold'));

        self::assertFalse($tripped->enabled);
        self::assertNotNull($tripped->autoDisabledAt);
        self::assertSame('errors exceeded threshold', $tripped->autoDisableReason);
    }

    public function testListAllOrdersByKeyAlpha(): void
    {
        $this->repo->save($this->makeFlag(id: 1, key: 'zebra'));
        $this->repo->save($this->makeFlag(id: 2, key: 'alpha'));
        $this->repo->save($this->makeFlag(id: 3, key: 'middle'));

        $list = $this->repo->listAll();

        self::assertSame(['alpha', 'middle', 'zebra'], array_map(
            static fn (FeatureFlag $f): string => $f->key->toString(),
            $list,
        ));
    }

    public function testDeleteRemovesRow(): void
    {
        $this->repo->save($this->makeFlag(id: 1));
        $this->repo->delete(1);

        self::assertNull($this->repo->findById(1));
    }

    public function testNextIdReturnsOneOnEmptyTable(): void
    {
        self::assertSame(1, $this->repo->nextId());
    }

    public function testNextIdReturnsMaxPlusOne(): void
    {
        $this->repo->save($this->makeFlag(id: 7, key: 'a'));
        $this->repo->save($this->makeFlag(id: 3, key: 'b'));

        self::assertSame(8, $this->repo->nextId());
    }

    /**
     * @param array<string, mixed>|null $conditions
     */
    private function makeFlag(
        int $id,
        string $key = 'test.flag',
        bool $enabled = false,
        int $rollout = 0,
        ?array $conditions = null,
        int $threshold = 0,
    ): FeatureFlag {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');

        return new FeatureFlag(
            id: $id,
            key: new FeatureKey($key),
            description: 'desc',
            enabled: $enabled,
            rolloutPercent: $rollout,
            conditions: $conditions,
            errorThreshold: $threshold,
            errorWindowMinutes: 60,
            autoDisabledAt: null,
            autoDisableReason: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
