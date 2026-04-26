<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Feature;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;

final class FeatureFlagTest extends TestCase
{
    public function testStoresEveryField(): void
    {
        $now  = new DateTimeImmutable('2026-04-26 12:00:00');
        $flag = new FeatureFlag(
            id: 1,
            key: new FeatureKey('checkout.new_flow'),
            description: 'Roll out the new checkout flow.',
            enabled: true,
            rolloutPercent: 25,
            conditions: ['country' => 'jp'],
            errorThreshold: 50,
            errorWindowMinutes: 60,
            autoDisabledAt: null,
            autoDisableReason: null,
            createdAt: $now,
            updatedAt: $now,
        );

        self::assertSame(1, $flag->id);
        self::assertSame('checkout.new_flow', $flag->key->toString());
        self::assertTrue($flag->enabled);
        self::assertSame(25, $flag->rolloutPercent);
        self::assertSame(['country' => 'jp'], $flag->conditions);
        self::assertSame(50, $flag->errorThreshold);
        self::assertTrue($flag->autoDisableEnabled());
    }

    public function testAutoDisableEnabledRequiresPositiveThreshold(): void
    {
        $flag = $this->makeFlag(threshold: 0);
        self::assertFalse($flag->autoDisableEnabled());

        $flag = $this->makeFlag(threshold: 1);
        self::assertTrue($flag->autoDisableEnabled());
    }

    public function testWithEnabledIsNonMutating(): void
    {
        $on  = $this->makeFlag(enabled: true);
        $off = $on->withEnabled(false);

        self::assertNotSame($on, $off);
        self::assertTrue($on->enabled);
        self::assertFalse($off->enabled);
    }

    public function testTripBreakerSetsDisabledAndReason(): void
    {
        $on  = $this->makeFlag(enabled: true);
        $at  = new DateTimeImmutable('2026-04-26 13:00:00');
        $off = $on->tripBreaker($at, 'errors exceeded threshold of 50 in last 60 min');

        self::assertFalse($off->enabled);
        self::assertSame($at, $off->autoDisabledAt);
        self::assertSame('errors exceeded threshold of 50 in last 60 min', $off->autoDisableReason);
    }

    public function testRejectsNonPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeFlag(id: 0);
    }

    public function testRejectsEmptyDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeFlag(description: '');
    }

    public function testRejectsRolloutOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeFlag(rollout: 101);
    }

    public function testRejectsNegativeRollout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeFlag(rollout: -1);
    }

    public function testRejectsNegativeThreshold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeFlag(threshold: -1);
    }

    public function testRejectsZeroWindow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeFlag(window: 0);
    }

    private function makeFlag(
        int $id = 1,
        string $description = 'desc',
        bool $enabled = false,
        int $rollout = 0,
        int $threshold = 0,
        int $window = 60,
    ): FeatureFlag {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');

        return new FeatureFlag(
            id: $id,
            key: new FeatureKey('test.flag'),
            description: $description,
            enabled: $enabled,
            rolloutPercent: $rollout,
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
