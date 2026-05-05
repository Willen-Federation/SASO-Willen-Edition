<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

use InvalidArgumentException;

final readonly class ShelfDimensionMetadata
{
    public function __construct(
        public string $name,
        public string $description,
        public ShelfDimensionType $type,
        public int $position,
        public bool $enabled,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('name must not be empty.');
        }
        if ($position < 1 || $position > 10) {
            throw new InvalidArgumentException('position must be between 1 and 10.');
        }
    }

    public static function fromArray(array $data): self
    {
        $typeValue = $data['type'] ?? 'numeric';
        $type = is_string($typeValue) ? ShelfDimensionType::from($typeValue) : $typeValue;

        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            type: $type,
            position: $data['position'] ?? 1,
            enabled: $data['enabled'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'position' => $this->position,
            'enabled' => $this->enabled,
        ];
    }
}
