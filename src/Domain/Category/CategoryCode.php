<?php

declare(strict_types=1);

namespace Saso\Domain\Category;

use InvalidArgumentException;

/**
 * Immutable value object for a category code.
 *
 * Rules: 1–64 chars, uppercase alphanumeric segments joined by hyphens.
 * Examples: FOOD, FOOD-FRESH, ELEC-PC-LAPTOP
 */
final readonly class CategoryCode
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('CategoryCode must not be empty.');
        }
        if (strlen($trimmed) > 64) {
            throw new InvalidArgumentException('CategoryCode must not exceed 64 characters.');
        }
        if (!preg_match('/^[A-Z0-9]+(-[A-Z0-9]+)*$/', $trimmed)) {
            throw new InvalidArgumentException(
                'CategoryCode must be uppercase alphanumeric segments joined by hyphens (e.g. FOOD-FRESH).',
            );
        }
        $this->value = $trimmed;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
