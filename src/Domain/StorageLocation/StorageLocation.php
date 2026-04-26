<?php

declare(strict_types=1);

namespace Saso\Domain\StorageLocation;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * One node in the storage-location tree (cf. ADR 0011).
 *
 * `parentId = null` ⇒ root (depth 0). Children carry `depth =
 * parent.depth + 1` and a `position` that orders siblings under the
 * parent. The aggregate enforces these invariants at construction; the
 * repository (M6-E1) trusts the value to be already valid.
 */
final readonly class StorageLocation
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public LocationCode $code,
        public string $name,
        public int $position,
        public int $depth,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('StorageLocation.id must be a positive integer.');
        }
        if ($parentId !== null && $parentId < 1) {
            throw new InvalidArgumentException('StorageLocation.parentId must be a positive integer or null.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('StorageLocation.name must not be empty.');
        }
        if ($position < 0) {
            throw new InvalidArgumentException('StorageLocation.position must be ≥ 0.');
        }
        if ($depth < 0) {
            throw new InvalidArgumentException('StorageLocation.depth must be ≥ 0.');
        }
        if ($parentId === null && $depth !== 0) {
            throw new InvalidArgumentException('StorageLocation.depth must be 0 when parentId is null.');
        }
        if ($parentId !== null && $depth === 0) {
            throw new InvalidArgumentException('StorageLocation.depth must be ≥ 1 when parentId is set.');
        }
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            parentId: $this->parentId,
            code: $this->code,
            name: $name,
            position: $this->position,
            depth: $this->depth,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    public function withPosition(int $position): self
    {
        return new self(
            id: $this->id,
            parentId: $this->parentId,
            code: $this->code,
            name: $this->name,
            position: $position,
            depth: $this->depth,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
