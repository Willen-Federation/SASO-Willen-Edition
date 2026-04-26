<?php

declare(strict_types=1);

namespace Saso\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Feature\FeatureFlagAuditEntry;
use Saso\Domain\Feature\Repository\FeatureFlagAuditRepository;

/**
 * PDO-backed {@see FeatureFlagAuditRepository}.
 *
 * `record()` issues a single INSERT — the audit log is append-only,
 * so there is no UPDATE/DELETE surface. `listForFlag()` returns the
 * most recent `$limit` entries newest-first, which is what the admin
 * UI shows when an operator clicks "history".
 */
final class PdoFeatureFlagAuditRepository implements FeatureFlagAuditRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function record(
        string $flagKey,
        bool $oldEnabled,
        bool $newEnabled,
        string $changedBy,
        ?string $reason = null,
    ): void {
        $now  = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO feature_flag_audit (flag_key, old_enabled, new_enabled, '.
            'changed_by, changed_at, reason) VALUES (:key, :old, :new, :by, :at, :reason)',
        );
        $stmt->bindValue('key', $flagKey);
        $stmt->bindValue('old', $oldEnabled ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue('new', $newEnabled ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue('by', $changedBy);
        $stmt->bindValue('at', $now);
        $stmt->bindValue('reason', $reason, $reason === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    public function listForFlag(string $flagKey, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM feature_flag_audit WHERE flag_key = :key '.
            'ORDER BY changed_at DESC, id DESC LIMIT :limit',
        );
        $stmt->bindValue('key', $flagKey);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn (array $row): FeatureFlagAuditEntry => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): FeatureFlagAuditEntry
    {
        return new FeatureFlagAuditEntry(
            id: (int) $row['id'],
            flagKey: (string) $row['flag_key'],
            oldEnabled: (int) $row['old_enabled'] === 1,
            newEnabled: (int) $row['new_enabled'] === 1,
            changedBy: (string) $row['changed_by'],
            changedAt: new DateTimeImmutable((string) $row['changed_at'], $this->timezone),
            reason: isset($row['reason']) && is_string($row['reason']) ? $row['reason'] : null,
        );
    }
}
