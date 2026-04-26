<?php

declare(strict_types=1);

namespace Saso\Domain\Feature;

use DateTimeImmutable;

/**
 * Immutable view of a single `feature_flag_audit` row.
 *
 * `changedBy` is either a member id (operator-driven flip) or the
 * literal string `circuit_breaker` when the cron sweep auto-disabled
 * the flag. `flagKey` is denormalised so audit history remains
 * decodable after a flag row is deleted.
 */
final readonly class FeatureFlagAuditEntry
{
    public function __construct(
        public int $id,
        public string $flagKey,
        public bool $oldEnabled,
        public bool $newEnabled,
        public string $changedBy,
        public DateTimeImmutable $changedAt,
        public ?string $reason,
    ) {
    }

    public function isCircuitBreakerEvent(): bool
    {
        return $this->changedBy === 'circuit_breaker';
    }
}
