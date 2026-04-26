<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

use Saso\Domain\Setting\Exception\SettingNotFoundException;

/**
 * Read/write contract for `system_setting` rows (cf. ADR 0006).
 *
 * The service owns the database storage and the audit log. The wider
 * precedence chain (`.env` → real env var → `system_setting` →
 * `config.json` → hard-coded default) is composed at the consumer
 * layer — this service deals only with the database tier.
 */
interface SystemSettingService
{
    public function get(SettingKey $key): ?SettingValue;

    /**
     * @throws SettingNotFoundException when no row exists for the key
     */
    public function require(SettingKey $key): SettingValue;

    public function set(
        SettingKey $key,
        SettingValue $value,
        string $changedBy,
        ?string $reason = null,
    ): void;

    public function delete(SettingKey $key, string $changedBy, ?string $reason = null): void;

    /**
     * @return array<string, SettingValue> keyed by the canonical key string
     */
    public function all(): array;
}
