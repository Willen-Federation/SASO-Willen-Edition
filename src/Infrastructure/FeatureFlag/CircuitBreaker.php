<?php

declare(strict_types=1);

namespace Saso\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

final class CircuitBreaker
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly FeatureFlagRepository $flagRepo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function run(): void
    {
        $now = new DateTimeImmutable('now', $this->timezone);
        
        $flags = $this->flagRepo->listAll();
        
        foreach ($flags as $flag) {
            if (!$flag->enabled || $flag->errorThreshold === 0) {
                continue;
            }

            $windowStart = $now->modify("-{$flag->errorWindowMinutes} minutes");

            $stmt = $this->pdo->prepare(
                'SELECT SUM(count) as total_errors FROM error_log_aggregate ' .
                'WHERE feature_key = :key AND window_start >= :window_start'
            );
            $stmt->bindValue('key', $flag->key->toString());
            $stmt->bindValue('window_start', $windowStart->format('Y-m-d H:i:s'));
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalErrors = (int) ($row['total_errors'] ?? 0);

            if ($totalErrors >= $flag->errorThreshold) {
                // Auto-disable
                $disabledFlag = new FeatureFlag(
                    id: $flag->id,
                    key: $flag->key,
                    description: $flag->description,
                    enabled: false,
                    rolloutPercent: $flag->rolloutPercent,
                    conditions: $flag->conditions,
                    errorThreshold: $flag->errorThreshold,
                    errorWindowMinutes: $flag->errorWindowMinutes,
                    autoDisabledAt: $now,
                    autoDisableReason: "Error threshold reached ($totalErrors >= {$flag->errorThreshold})",
                    createdAt: $flag->createdAt,
                    updatedAt: $now
                );
                
                $this->flagRepo->save($disabledFlag);

                // Audit log
                $auditStmt = $this->pdo->prepare(
                    'INSERT INTO feature_flag_audit (flag_key, old_enabled, new_enabled, changed_by, changed_at, reason) ' .
                    'VALUES (:key, :old, :new, :by, :at, :reason)'
                );
                $auditStmt->execute([
                    'key' => $flag->key->toString(),
                    'old' => 1,
                    'new' => 0,
                    'by' => 'circuit_breaker',
                    'at' => $now->format('Y-m-d H:i:s'),
                    'reason' => "Error threshold reached ($totalErrors errors in {$flag->errorWindowMinutes} min)",
                ]);
            }
        }
    }
}
