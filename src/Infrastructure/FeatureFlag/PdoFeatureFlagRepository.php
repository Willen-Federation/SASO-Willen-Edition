<?php

declare(strict_types=1);

namespace Saso\Infrastructure\FeatureFlag;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

/**
 * PDO-backed {@see FeatureFlagRepository}.
 *
 * SQL is portable across MariaDB (production) and SQLite (test
 * substrate). Boolean columns persist as INTEGER 0/1 because SQLite
 * has no native BOOLEAN; MariaDB transparently accepts both shapes.
 * `conditions` is JSON text on both adapters — round-trips through
 * `json_encode` / `json_decode` for the string-keyed maps we use.
 */
final class PdoFeatureFlagRepository implements FeatureFlagRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findByKey(FeatureKey $key): ?FeatureFlag
    {
        $stmt = $this->pdo->prepare('SELECT * FROM feature_flag WHERE key_name = :key');
        $stmt->execute(['key' => $key->toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findById(int $id): ?FeatureFlag
    {
        $stmt = $this->pdo->prepare('SELECT * FROM feature_flag WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM feature_flag ORDER BY key_name ASC');
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): FeatureFlag => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function nextId(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM feature_flag');
        if ($stmt === false) {
            return 1;
        }

        return (int) $stmt->fetchColumn();
    }

    public function save(FeatureFlag $flag): FeatureFlag
    {
        $now = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');

        $existing = $this->findById($flag->id);
        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO feature_flag (id, key_name, description, enabled, rollout_percent, '.
                'conditions, error_threshold, error_window_min, auto_disabled_at, '.
                'auto_disable_reason, created_at, updated_at) VALUES (:id, :key, :desc, :enabled, '.
                ':rollout, :conditions, :threshold, :window, :auto_at, :auto_reason, :ca, :ua)',
            );
            $stmt->bindValue('id', $flag->id, PDO::PARAM_INT);
            $stmt->bindValue('key', $flag->key->toString());
            $stmt->bindValue('desc', $flag->description);
            $stmt->bindValue('enabled', $flag->enabled ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('rollout', $flag->rolloutPercent, PDO::PARAM_INT);
            $stmt->bindValue('conditions', $flag->conditions === null ? null : self::encodeJson($flag->conditions));
            $stmt->bindValue('threshold', $flag->errorThreshold, PDO::PARAM_INT);
            $stmt->bindValue('window', $flag->errorWindowMinutes, PDO::PARAM_INT);
            $stmt->bindValue('auto_at', $flag->autoDisabledAt?->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('auto_reason', $flag->autoDisableReason);
            $stmt->bindValue('ca', $flag->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE feature_flag SET key_name = :key, description = :desc, enabled = :enabled, '.
                'rollout_percent = :rollout, conditions = :conditions, error_threshold = :threshold, '.
                'error_window_min = :window, auto_disabled_at = :auto_at, '.
                'auto_disable_reason = :auto_reason, updated_at = :ua WHERE id = :id',
            );
            $stmt->bindValue('id', $flag->id, PDO::PARAM_INT);
            $stmt->bindValue('key', $flag->key->toString());
            $stmt->bindValue('desc', $flag->description);
            $stmt->bindValue('enabled', $flag->enabled ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('rollout', $flag->rolloutPercent, PDO::PARAM_INT);
            $stmt->bindValue('conditions', $flag->conditions === null ? null : self::encodeJson($flag->conditions));
            $stmt->bindValue('threshold', $flag->errorThreshold, PDO::PARAM_INT);
            $stmt->bindValue('window', $flag->errorWindowMinutes, PDO::PARAM_INT);
            $stmt->bindValue('auto_at', $flag->autoDisabledAt?->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('auto_reason', $flag->autoDisableReason);
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        }

        $reread = $this->findById($flag->id);
        if ($reread === null) {
            throw new \RuntimeException(sprintf(
                'PdoFeatureFlagRepository::save lost row %d after write.',
                $flag->id,
            ));
        }

        return $reread;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM feature_flag WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): FeatureFlag
    {
        $conditions = null;
        if (isset($row['conditions']) && is_string($row['conditions']) && $row['conditions'] !== '') {
            $decoded    = json_decode($row['conditions'], associative: true);
            $conditions = is_array($decoded) ? $decoded : null;
        }

        $autoAt = $row['auto_disabled_at'] ?? null;

        return new FeatureFlag(
            id: (int) $row['id'],
            key: new FeatureKey((string) $row['key_name']),
            description: (string) $row['description'],
            enabled: (int) $row['enabled'] === 1,
            rolloutPercent: (int) $row['rollout_percent'],
            conditions: $conditions,
            errorThreshold: (int) $row['error_threshold'],
            errorWindowMinutes: (int) $row['error_window_min'],
            autoDisabledAt: is_string($autoAt) && $autoAt !== ''
                ? new DateTimeImmutable($autoAt, $this->timezone)
                : null,
            autoDisableReason: isset($row['auto_disable_reason']) && is_string($row['auto_disable_reason'])
                ? $row['auto_disable_reason']
                : null,
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function encodeJson(array $payload): string
    {
        return (string) json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
