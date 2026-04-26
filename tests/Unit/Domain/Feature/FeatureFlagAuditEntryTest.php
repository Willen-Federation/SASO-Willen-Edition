<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Feature;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureFlagAuditEntry;

final class FeatureFlagAuditEntryTest extends TestCase
{
    public function testStoresEveryField(): void
    {
        $now   = new DateTimeImmutable('2026-04-26 12:00:00');
        $entry = new FeatureFlagAuditEntry(
            id: 7,
            flagKey: 'checkout.new_flow',
            oldEnabled: true,
            newEnabled: false,
            changedBy: 'circuit_breaker',
            changedAt: $now,
            reason: 'errors exceeded threshold',
        );

        self::assertSame(7, $entry->id);
        self::assertSame('checkout.new_flow', $entry->flagKey);
        self::assertTrue($entry->oldEnabled);
        self::assertFalse($entry->newEnabled);
        self::assertSame('errors exceeded threshold', $entry->reason);
    }

    public function testIsCircuitBreakerEvent(): void
    {
        $now = new DateTimeImmutable();

        $byBreaker = new FeatureFlagAuditEntry(
            id: 1,
            flagKey: 'k',
            oldEnabled: true,
            newEnabled: false,
            changedBy: 'circuit_breaker',
            changedAt: $now,
            reason: null,
        );
        self::assertTrue($byBreaker->isCircuitBreakerEvent());

        $byHuman = new FeatureFlagAuditEntry(
            id: 2,
            flagKey: 'k',
            oldEnabled: true,
            newEnabled: false,
            changedBy: 'admin-1',
            changedAt: $now,
            reason: null,
        );
        self::assertFalse($byHuman->isCircuitBreakerEvent());
    }
}
