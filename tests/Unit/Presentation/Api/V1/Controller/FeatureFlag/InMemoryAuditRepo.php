<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use Saso\Domain\Feature\FeatureFlagAuditEntry;
use Saso\Domain\Feature\Repository\FeatureFlagAuditRepository;

/**
 * @internal Used by FeatureFlag controller tests to observe audit writes.
 */
final class InMemoryAuditRepo implements FeatureFlagAuditRepository
{
    /** @var list<array{key: string, old: bool, new: bool, by: string, reason: ?string}> */
    public array $records = [];

    public function record(
        string $flagKey,
        bool $oldEnabled,
        bool $newEnabled,
        string $changedBy,
        ?string $reason = null,
    ): void {
        $this->records[] = [
            'key'    => $flagKey,
            'old'    => $oldEnabled,
            'new'    => $newEnabled,
            'by'     => $changedBy,
            'reason' => $reason,
        ];
    }

    /** @return list<FeatureFlagAuditEntry> */
    public function listForFlag(string $flagKey, int $limit = 50): array
    {
        $out = [];
        foreach ($this->records as $i => $row) {
            if ($row['key'] === $flagKey) {
                $out[] = new FeatureFlagAuditEntry(
                    id: $i + 1,
                    flagKey: $row['key'],
                    oldEnabled: $row['old'],
                    newEnabled: $row['new'],
                    changedBy: $row['by'],
                    changedAt: new DateTimeImmutable(),
                    reason: $row['reason'],
                );
            }
        }
        return array_slice($out, 0, $limit);
    }
}
