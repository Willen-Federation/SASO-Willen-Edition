<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * Aggregate root for `verification_session`. Immutable; transitions
 * (`complete()`, `abandon()`) return new instances.
 */
final readonly class VerificationSession
{
    public function __construct(
        public int $id,
        public VerificationMode $mode,
        public ?string $areaCode,
        public ?int $scopeLocationId,
        public ?string $startedBy,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt,
        public VerificationStatus $status,
        public ?string $notes,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('VerificationSession.id must be a positive integer.');
        }
        if ($status === VerificationStatus::Completed && $completedAt === null) {
            throw new InvalidArgumentException('Completed session must carry completedAt.');
        }
    }

    public function isActive(): bool
    {
        return $this->status === VerificationStatus::Active;
    }

    public function complete(DateTimeImmutable $at): self
    {
        if (!$this->isActive()) {
            throw new DomainException('Only active sessions can be completed.');
        }
        return new self(
            id:               $this->id,
            mode:             $this->mode,
            areaCode:         $this->areaCode,
            scopeLocationId:  $this->scopeLocationId,
            startedBy:        $this->startedBy,
            startedAt:        $this->startedAt,
            completedAt:      $at,
            status:           VerificationStatus::Completed,
            notes:            $this->notes,
        );
    }

    public function abandon(DateTimeImmutable $at, string $reason): self
    {
        if (!$this->isActive()) {
            throw new DomainException('Only active sessions can be abandoned.');
        }
        return new self(
            id:               $this->id,
            mode:             $this->mode,
            areaCode:         $this->areaCode,
            scopeLocationId:  $this->scopeLocationId,
            startedBy:        $this->startedBy,
            startedAt:        $this->startedAt,
            completedAt:      $at,
            status:           VerificationStatus::Abandoned,
            notes:            $reason,
        );
    }
}
