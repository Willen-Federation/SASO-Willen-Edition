<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

/**
 * Read-model returned by VerificationService::summary().
 *
 * Pure DTO — counts are precomputed by the service so the report screen
 * can render without re-running aggregation queries.
 */
final readonly class VerificationSummary
{
    public function __construct(
        public int $sessionId,
        public int $expectedCount,
        public int $matchCount,
        public int $missingCount,
        public int $unexpectedCount,
        public int $mismatchLocationCount,
        public int $unknownCodeCount,
    ) {
    }

    public function totalEvents(): int
    {
        return $this->matchCount
            + $this->missingCount
            + $this->unexpectedCount
            + $this->mismatchLocationCount
            + $this->unknownCodeCount;
    }
}
