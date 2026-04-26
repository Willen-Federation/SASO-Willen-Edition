<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Setting;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PDO;
use Saso\Domain\Setting\Exception\SettingNotFoundException;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingType;
use Saso\Domain\Setting\SettingValue;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

/**
 * PDO-backed implementation of {@see SystemSettingService}.
 *
 * Storage layout:
 *   * `system_setting`        — current value per key.
 *   * `system_setting_audit`  — append-only history of every write.
 *
 * Reads are cached for the lifetime of the service instance (= one
 * request). Writes invalidate the cache for the touched key — admin
 * UI flows that read-then-write therefore see their own update.
 *
 * Secrets ({@see SettingType::Secret}) are encrypted at rest via the
 * supplied {@see SecretEncryptor}; reads decrypt transparently. The
 * audit row stores the ciphertext, never the plaintext, so rotating
 * `APP_KEY` does not retroactively expose the audit history.
 *
 * SQL is portable enough to run on both MariaDB / MySQL (production)
 * and SQLite (the test substrate). The upsert is implemented as
 * SELECT-then-INSERT-or-UPDATE rather than `ON DUPLICATE KEY UPDATE`
 * so the same code path exercises in both environments. The race
 * window between SELECT and INSERT is acceptable for admin traffic;
 * if two concurrent admins set the same key, one INSERT raises a
 * unique-constraint violation that the caller can retry as an UPDATE.
 */
final class PdoSystemSettingService implements SystemSettingService
{
    /**
     * @var array<string, SettingValue|false> request-scoped cache;
     *                                        `false` marks a key proven absent.
     */
    private array $cache = [];

    public function __construct(
        private readonly PDO $pdo,
        private readonly SecretEncryptor $encryptor,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function get(SettingKey $key): ?SettingValue
    {
        $name = $key->toString();
        if (array_key_exists($name, $this->cache)) {
            $cached = $this->cache[$name];

            return $cached === false ? null : $cached;
        }

        $stmt = $this->pdo->prepare(
            'SELECT value, value_type, encrypted FROM system_setting WHERE `key` = :key',
        );
        $stmt->execute(['key' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            $this->cache[$name] = false;

            return null;
        }

        $value = $this->decodeRow(
            (string) $row['value'],
            (string) $row['value_type'],
            (int) $row['encrypted'] === 1,
        );

        $this->cache[$name] = $value;

        return $value;
    }

    public function require(SettingKey $key): SettingValue
    {
        $value = $this->get($key);
        if ($value === null) {
            throw SettingNotFoundException::for($key);
        }

        return $value;
    }

    public function set(
        SettingKey $key,
        SettingValue $value,
        string $changedBy,
        ?string $reason = null,
    ): void {
        $name        = $key->toString();
        $now         = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $isSecret    = $value->type->isSecret();
        $storedBytes = $isSecret ? $this->encryptor->encrypt($value->raw) : $value->raw;

        $previous = $this->fetchRawRow($name);

        if ($previous === null) {
            $insert = $this->pdo->prepare(
                'INSERT INTO system_setting (`key`, value, value_type, encrypted, updated_at, updated_by) '.
                'VALUES (:key, :value, :type, :encrypted, :updated_at, :updated_by)',
            );
            $insert->execute([
                'key'        => $name,
                'value'      => $storedBytes,
                'type'       => $value->type->value,
                'encrypted'  => $isSecret ? 1 : 0,
                'updated_at' => $now,
                'updated_by' => $changedBy,
            ]);
        } else {
            $update = $this->pdo->prepare(
                'UPDATE system_setting SET value = :value, value_type = :type, encrypted = :encrypted, '.
                'updated_at = :updated_at, updated_by = :updated_by WHERE `key` = :key',
            );
            $update->execute([
                'value'      => $storedBytes,
                'type'       => $value->type->value,
                'encrypted'  => $isSecret ? 1 : 0,
                'updated_at' => $now,
                'updated_by' => $changedBy,
                'key'        => $name,
            ]);
        }

        $this->writeAudit(
            key: $name,
            oldValue: $previous['value'] ?? null,
            newValue: $storedBytes,
            changedBy: $changedBy,
            changedAt: $now,
            reason: $reason,
        );

        $this->cache[$name] = $value;
    }

    public function delete(SettingKey $key, string $changedBy, ?string $reason = null): void
    {
        $name     = $key->toString();
        $previous = $this->fetchRawRow($name);
        if ($previous === null) {
            return;
        }

        $delete = $this->pdo->prepare('DELETE FROM system_setting WHERE `key` = :key');
        $delete->execute(['key' => $name]);

        $now = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $this->writeAudit(
            key: $name,
            oldValue: $previous['value'],
            newValue: null,
            changedBy: $changedBy,
            changedAt: $now,
            reason: $reason,
        );

        $this->cache[$name] = false;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT `key`, value, value_type, encrypted FROM system_setting ORDER BY `key` ASC',
        );
        if ($stmt === false) {
            return [];
        }

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key       = (string) $row['key'];
            $value     = $this->decodeRow(
                (string) $row['value'],
                (string) $row['value_type'],
                (int) $row['encrypted'] === 1,
            );
            $out[$key]         = $value;
            $this->cache[$key] = $value;
        }

        return $out;
    }

    /**
     * @return array{value: string, value_type: string, encrypted: int}|null
     */
    private function fetchRawRow(string $key): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT value, value_type, encrypted FROM system_setting WHERE `key` = :key',
        );
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return [
            'value'      => (string) $row['value'],
            'value_type' => (string) $row['value_type'],
            'encrypted'  => (int) $row['encrypted'],
        ];
    }

    private function decodeRow(string $rawBytes, string $type, bool $encrypted): SettingValue
    {
        $plaintext = $encrypted ? $this->encryptor->decrypt($rawBytes) : $rawBytes;

        return new SettingValue(
            raw: $plaintext,
            type: SettingType::from($type),
        );
    }

    private function writeAudit(
        string $key,
        ?string $oldValue,
        ?string $newValue,
        string $changedBy,
        string $changedAt,
        ?string $reason,
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO system_setting_audit (`key`, old_value, new_value, changed_by, changed_at, reason) '.
            'VALUES (:key, :old_value, :new_value, :changed_by, :changed_at, :reason)',
        );
        $stmt->bindValue('key', $key);
        $stmt->bindValue('old_value', $oldValue, $oldValue === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $stmt->bindValue('new_value', $newValue, $newValue === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $stmt->bindValue('changed_by', $changedBy);
        $stmt->bindValue('changed_at', $changedAt);
        $stmt->bindValue('reason', $reason, $reason === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Test convenience — formats a timestamp the same way write paths do.
     * Not part of the {@see SystemSettingService} contract.
     */
    public function nowString(): string
    {
        return (new DateTimeImmutable('now', $this->timezone))->format(DateTimeInterface::ATOM);
    }
}
