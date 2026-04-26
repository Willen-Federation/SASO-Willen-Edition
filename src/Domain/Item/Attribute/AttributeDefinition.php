<?php

declare(strict_types=1);

namespace Saso\Domain\Item\Attribute;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate representing one row of `item_attribute_definition` (cf.
 * ADR 0011).
 *
 * Constructor invariants enforce the EAV-shape rules: enum types
 * must declare a non-empty `enumValues` list; non-enum types must
 * not; the regex (if present) must be a syntactically valid PCRE
 * pattern. Adapters trust this shape — the repository never
 * persists an inconsistent row.
 */
final readonly class AttributeDefinition
{
    /**
     * @param list<string>|null $enumValues required for value_type = enum
     */
    public function __construct(
        public int $id,
        public AttributeCode $code,
        public string $labelEn,
        public string $labelJa,
        public AttributeValueType $valueType,
        public ?string $unit,
        public bool $required,
        public ?array $enumValues,
        public ?string $validationRegex,
        public int $sortOrder,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('AttributeDefinition.id must be a positive integer.');
        }
        if ($labelEn === '') {
            throw new InvalidArgumentException('AttributeDefinition.labelEn must not be empty.');
        }
        if ($labelJa === '') {
            throw new InvalidArgumentException('AttributeDefinition.labelJa must not be empty.');
        }
        if ($valueType->requiresEnumValues()) {
            if ($enumValues === null || $enumValues === []) {
                throw new InvalidArgumentException(
                    'AttributeDefinition.enumValues must be a non-empty list when valueType = enum.',
                );
            }
        } elseif ($enumValues !== null) {
            throw new InvalidArgumentException(
                'AttributeDefinition.enumValues must be null when valueType is not enum.',
            );
        }
        if ($validationRegex !== null && @preg_match('#'.$validationRegex.'#u', '') === false) {
            throw new InvalidArgumentException(sprintf(
                'AttributeDefinition.validationRegex is not a valid PCRE pattern: %s',
                $validationRegex,
            ));
        }
        if ($sortOrder < 0) {
            throw new InvalidArgumentException('AttributeDefinition.sortOrder must be ≥ 0.');
        }
    }

    public function localisedLabel(string $locale): string
    {
        return match ($locale) {
            'ja' => $this->labelJa,
            default => $this->labelEn,
        };
    }
}
