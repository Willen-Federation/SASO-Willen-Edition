<?php

declare(strict_types=1);

namespace Saso\Domain\Feature\Repository;

use Saso\Domain\Feature\FeatureFlagAuditEntry;

/**
 * Append-only audit log for `feature_flag` writes (cf. ADR 0005).
 *
 * Operators flipping a flag from the admin UI write through `record()`;
 * the cron circuit breaker uses the same method with `changedBy =
 * "circuit_breaker"`. There is no update / delete surface — the audit
 * is meant to be queryable forever.
 */
interface FeatureFlagAuditRepository
{
    public function record(
        string $flagKey,
        bool $oldEnabled,
        bool $newEnabled,
        string $changedBy,
        ?string $reason = null,
    ): void;

    /**
     * @return list<FeatureFlagAuditEntry>
     */
    public function listForFlag(string $flagKey, int $limit = 50): array;
}
