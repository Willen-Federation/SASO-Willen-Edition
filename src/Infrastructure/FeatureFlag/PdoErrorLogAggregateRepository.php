<?php

declare(strict_types=1);

namespace Saso\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\ErrorLogAggregateRepository;

/**
 * PDO-backed {@see ErrorLogAggregateRepository}.
 *
 * Each error becomes a one-row "tick" with `count = 1`. The
 * cron breaker (M4-E2) sums `count` over the configured window. We
 * do not bucket on write because clock skew across application hosts
 * makes shared bucket boundaries fragile; storing raw ticks plus a
 * window-end column means the sweep query can be exact.
 */
final class PdoErrorLogAggregateRepository implements ErrorLogAggregateRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function recordError(FeatureKey $key, string $errorCode, DateTimeImmutable $observedAt): void
    {
        $observedUtc = $observedAt->setTimezone($this->timezone)->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'INSERT INTO error_log_aggregate (feature_key, error_code, count, window_start, window_end) '.
            'VALUES (:key, :code, 1, :ws, :we)',
        );
        $stmt->execute([
            'key'  => $key->toString(),
            'code' => $errorCode,
            'ws'   => $observedUtc,
            'we'   => $observedUtc,
        ]);
    }

    public function countSince(FeatureKey $key, DateTimeImmutable $since): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(count), 0) FROM error_log_aggregate '.
            'WHERE feature_key = :key AND window_end >= :since',
        );
        $stmt->execute([
            'key'   => $key->toString(),
            'since' => $since->setTimezone($this->timezone)->format('Y-m-d H:i:s'),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function purgeOlderThan(DateTimeImmutable $cutoff): int
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM error_log_aggregate WHERE window_end < :cutoff',
        );
        $stmt->execute([
            'cutoff' => $cutoff->setTimezone($this->timezone)->format('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount();
    }
}
