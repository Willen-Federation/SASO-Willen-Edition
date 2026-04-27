<?php

declare(strict_types=1);

namespace Saso\Domain\Category;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * One node in the category classification tree.
 *
 * `parentId = null` → root category (depth 0). Children inherit
 * `depth = parent.depth + 1`. `nameEn` and `nameJa` carry bilingual
 * labels for AI / human display.
 */
final readonly class Category
{
    public function __construct(
        public int $id,
        public CategoryCode $code,
        public string $nameEn,
        public string $nameJa,
        public ?int $parentId,
        public int $depth,
        public int $sortOrder,
        public ?string $description,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('Category.id must be a positive integer.');
        }
        if ($nameEn === '') {
            throw new InvalidArgumentException('Category.nameEn must not be empty.');
        }
        if ($nameJa === '') {
            throw new InvalidArgumentException('Category.nameJa must not be empty.');
        }
        if ($parentId !== null && $parentId < 1) {
            throw new InvalidArgumentException('Category.parentId must be a positive integer or null.');
        }
        if ($depth < 0) {
            throw new InvalidArgumentException('Category.depth must be ≥ 0.');
        }
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }
}
