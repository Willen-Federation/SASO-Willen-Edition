<?php

declare(strict_types=1);

namespace Saso\Domain\Feature\Repository;

use DateTimeImmutable;
use Saso\Domain\Feature\FeatureKey;

/**
 * Write contract for `error_log_aggregate` rows (cf. ADR 0005).
 *
 * The shape is intentionally tiny: `recordError()` is on the hot path
 * (called from {@see \Saso\Presentation\Http\Problem\ProblemExceptionHandler}),
 * `countSince()` is read by the cron breaker. There is no list/find
 * surface here — the aggregate table is a metric stream, not a domain
 * entity.
 */
interface ErrorLogAggregateRepository
{
    public function recordError(FeatureKey $key, string $errorCode, DateTimeImmutable $observedAt): void;

    public function countSince(FeatureKey $key, DateTimeImmutable $since): int;

    public function purgeOlderThan(DateTimeImmutable $cutoff): int;
}
