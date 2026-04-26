<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Immutable view of one `plugin_registry` row (cf. ADR 0015).
 *
 * Operators see this on the admin UI; the discovery loop reads it to
 * decide whether to call `Plugin::activate()` (first sighting) or
 * just `Plugin::register()` (already activated).
 */
final readonly class PluginRecord
{
    /**
     * @param array<string, mixed>|null $settings non-secret per-plugin preferences
     */
    public function __construct(
        public int $id,
        public string $package,
        public string $class,
        public string $name,
        public string $version,
        public DateTimeImmutable $activatedAt,
        public ?DateTimeImmutable $deactivatedAt,
        public ?DateTimeImmutable $lastSeenAt,
        public ?array $settings,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('PluginRecord.id must be a positive integer.');
        }
        if ($package === '') {
            throw new InvalidArgumentException('PluginRecord.package must not be empty.');
        }
        if (!str_contains($package, '/')) {
            throw new InvalidArgumentException(sprintf(
                'PluginRecord.package must be a Composer-style "vendor/name" (got %s).',
                $package,
            ));
        }
        if ($class === '') {
            throw new InvalidArgumentException('PluginRecord.class must not be empty.');
        }
    }

    public function isActive(): bool
    {
        return $this->deactivatedAt === null;
    }

    public function withDeactivatedAt(DateTimeImmutable $at): self
    {
        return new self(
            id: $this->id,
            package: $this->package,
            class: $this->class,
            name: $this->name,
            version: $this->version,
            activatedAt: $this->activatedAt,
            deactivatedAt: $at,
            lastSeenAt: $this->lastSeenAt,
            settings: $this->settings,
        );
    }

    public function withLastSeenAt(DateTimeImmutable $at): self
    {
        return new self(
            id: $this->id,
            package: $this->package,
            class: $this->class,
            name: $this->name,
            version: $this->version,
            activatedAt: $this->activatedAt,
            deactivatedAt: $this->deactivatedAt,
            lastSeenAt: $at,
            settings: $this->settings,
        );
    }
}
