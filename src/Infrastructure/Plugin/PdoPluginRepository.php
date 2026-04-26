<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Plugin;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Plugin\PluginRecord;
use Saso\Domain\Plugin\Repository\PluginRepository;

/**
 * PDO-backed {@see PluginRepository}.
 *
 * SQL is portable across MariaDB (production) and SQLite (test
 * substrate). `settings_json` is JSON-encoded on write and decoded
 * on read; both adapters round-trip strings correctly.
 *
 * `activate()` upserts: if the package row already exists it clears
 * `deactivated_at` and refreshes the version + class fields (the
 * package may have moved its plugin class between releases). The
 * `activated_at` of the original first-install is preserved so
 * audit history survives a re-activation.
 */
final class PdoPluginRepository implements PluginRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findByPackage(string $package): ?PluginRecord
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM plugin_registry WHERE package = :package',
        );
        $stmt->execute(['package' => $package]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findById(int $id): ?PluginRecord
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plugin_registry WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM plugin_registry WHERE deactivated_at IS NULL ORDER BY name ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): PluginRecord => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM plugin_registry ORDER BY name ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): PluginRecord => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function activate(PluginRecord $record): PluginRecord
    {
        $now      = new DateTimeImmutable('now', $this->timezone);
        $existing = $this->findByPackage($record->package);

        if ($existing === null) {
            $insert = $this->pdo->prepare(
                'INSERT INTO plugin_registry (package, class, name, version, '.
                'activated_at, deactivated_at, last_seen_at, settings_json) '.
                'VALUES (:package, :class, :name, :version, :activated, NULL, NULL, :settings)',
            );
            $insert->execute([
                'package'   => $record->package,
                'class'     => $record->class,
                'name'      => $record->name,
                'version'   => $record->version,
                'activated' => $now->format('Y-m-d H:i:s'),
                'settings'  => $record->settings === null ? null : self::encodeJson($record->settings),
            ]);
        } else {
            // Re-activation: preserve the original activated_at, clear
            // deactivated_at, refresh class + version (plugin authors
            // sometimes move their entry class between releases).
            $update = $this->pdo->prepare(
                'UPDATE plugin_registry SET class = :class, name = :name, '.
                'version = :version, deactivated_at = NULL, '.
                'settings_json = :settings WHERE package = :package',
            );
            $update->execute([
                'package'  => $record->package,
                'class'    => $record->class,
                'name'     => $record->name,
                'version'  => $record->version,
                'settings' => $record->settings === null ? null : self::encodeJson($record->settings),
            ]);
        }

        $reread = $this->findByPackage($record->package);
        if ($reread === null) {
            throw new \RuntimeException(sprintf(
                'PdoPluginRepository::activate lost row for package "%s" after write.',
                $record->package,
            ));
        }

        return $reread;
    }

    public function markSeen(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE plugin_registry SET last_seen_at = :now WHERE id = :id',
        );
        $stmt->execute([
            'id'  => $id,
            'now' => (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s'),
        ]);
    }

    public function deactivate(int $id, ?string $reason = null): void
    {
        // `reason` is part of the contract but `plugin_registry` does
        // not yet carry a column for it — operators see the timestamp
        // and the audit trail through their VCS / change-management
        // tooling. A future migration can add `deactivation_reason`
        // without breaking this signature.
        unset($reason);

        $stmt = $this->pdo->prepare(
            'UPDATE plugin_registry SET deactivated_at = :now WHERE id = :id',
        );
        $stmt->execute([
            'id'  => $id,
            'now' => (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): PluginRecord
    {
        $settings = null;
        if (isset($row['settings_json']) && is_string($row['settings_json']) && $row['settings_json'] !== '') {
            $decoded  = json_decode($row['settings_json'], associative: true);
            $settings = is_array($decoded) ? $decoded : null;
        }

        $deactivatedAt = $row['deactivated_at'] ?? null;
        $lastSeenAt    = $row['last_seen_at'] ?? null;

        return new PluginRecord(
            id: (int) $row['id'],
            package: (string) $row['package'],
            class: (string) $row['class'],
            name: (string) $row['name'],
            version: (string) $row['version'],
            activatedAt: new DateTimeImmutable((string) $row['activated_at'], $this->timezone),
            deactivatedAt: is_string($deactivatedAt) && $deactivatedAt !== ''
                ? new DateTimeImmutable($deactivatedAt, $this->timezone)
                : null,
            lastSeenAt: is_string($lastSeenAt) && $lastSeenAt !== ''
                ? new DateTimeImmutable($lastSeenAt, $this->timezone)
                : null,
            settings: $settings,
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
