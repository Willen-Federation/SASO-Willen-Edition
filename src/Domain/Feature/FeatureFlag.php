<?php

declare(strict_types=1);

namespace Saso\Domain\Feature;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Operator-managed feature flag with circuit-breaker policy
 * (cf. ADR 0005).
 *
 * `enabled` is the runtime answer to "should the flag's gated code path
 * run?". `rolloutPercent` is honoured only when `enabled` is true and
 * the OpenFeature provider applies it against the evaluation context's
 * targeting key.
 *
 * `errorThreshold` + `errorWindowMinutes` configure the circuit
 * breaker: when the cron sweep observes more than `errorThreshold`
 * failures attributed to this flag in the last `errorWindowMinutes`
 * minutes, it sets `enabled = false`, fills in `autoDisabledAt`/
 * `autoDisableReason`, and writes the audit row.
 * `errorThreshold = 0` means "never auto-disable".
 *
 * Mutators are explicit, named, and return a fresh aggregate so the
 * value is safe to share between request-scoped caches.
 */
final readonly class FeatureFlag
{
    /**
     * @param array<string, mixed>|null $conditions targeting rules
     */
    public function __construct(
        public int $id,
        public FeatureKey $key,
        public string $description,
        public bool $enabled,
        public int $rolloutPercent,
        public ?array $conditions,
        public int $errorThreshold,
        public int $errorWindowMinutes,
        public ?DateTimeImmutable $autoDisabledAt,
        public ?string $autoDisableReason,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('FeatureFlag.id must be a positive integer.');
        }
        if ($description === '') {
            throw new InvalidArgumentException('FeatureFlag.description must not be empty.');
        }
        if ($rolloutPercent < 0 || $rolloutPercent > 100) {
            throw new InvalidArgumentException(sprintf(
                'FeatureFlag.rolloutPercent must be 0-100 (got %d).',
                $rolloutPercent,
            ));
        }
        if ($errorThreshold < 0) {
            throw new InvalidArgumentException('FeatureFlag.errorThreshold must be ≥ 0.');
        }
        if ($errorWindowMinutes < 1) {
            throw new InvalidArgumentException('FeatureFlag.errorWindowMinutes must be ≥ 1.');
        }
    }

    public function withEnabled(bool $enabled): self
    {
        return new self(
            id: $this->id,
            key: $this->key,
            description: $this->description,
            enabled: $enabled,
            rolloutPercent: $this->rolloutPercent,
            conditions: $this->conditions,
            errorThreshold: $this->errorThreshold,
            errorWindowMinutes: $this->errorWindowMinutes,
            autoDisabledAt: $this->autoDisabledAt,
            autoDisableReason: $this->autoDisableReason,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    public function tripBreaker(DateTimeImmutable $at, string $reason): self
    {
        return new self(
            id: $this->id,
            key: $this->key,
            description: $this->description,
            enabled: false,
            rolloutPercent: $this->rolloutPercent,
            conditions: $this->conditions,
            errorThreshold: $this->errorThreshold,
            errorWindowMinutes: $this->errorWindowMinutes,
            autoDisabledAt: $at,
            autoDisableReason: $reason,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    public function autoDisableEnabled(): bool
    {
        return $this->errorThreshold > 0;
    }
}
