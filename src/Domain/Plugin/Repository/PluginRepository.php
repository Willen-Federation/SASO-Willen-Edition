<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Repository;

use Saso\Domain\Plugin\PluginRecord;

/**
 * Read/write contract for `plugin_registry` rows (cf. ADR 0015).
 *
 * The discovery loop calls `findByPackage()` to decide whether a
 * plugin needs `Plugin::activate()` (no row) or just `register()`
 * (row exists, `deactivated_at IS NULL`). `markSeen()` runs at the
 * tail of every successful registration so operators can spot
 * plugins that have stopped booting (`last_seen_at` stale).
 */
interface PluginRepository
{
    public function findByPackage(string $package): ?PluginRecord;

    public function findById(int $id): ?PluginRecord;

    /**
     * @return list<PluginRecord>
     */
    public function listActive(): array;

    /**
     * @return list<PluginRecord>
     */
    public function listAll(): array;

    public function activate(PluginRecord $record): PluginRecord;

    public function markSeen(int $id): void;

    public function deactivate(int $id, ?string $reason = null): void;
}
