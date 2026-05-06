<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

use InvalidArgumentException;

final readonly class ShelfDimensionConfig
{
    /** @var list<ShelfDimensionMetadata> */
    public array $dimensions;

    /**
     * @param list<ShelfDimensionMetadata> $dimensions
     */
    public function __construct(array $dimensions)
    {
        if (empty($dimensions)) {
            throw new InvalidArgumentException('dimensions must not be empty.');
        }
        if (count($dimensions) > 10) {
            throw new InvalidArgumentException('dimensions must not exceed 10.');
        }

        // Validate positions are unique and sequential for enabled dimensions
        $positions = [];
        foreach ($dimensions as $dim) {
            if ($dim->enabled && isset($positions[$dim->position])) {
                throw new InvalidArgumentException(
                    sprintf('Duplicate position %d for enabled dimensions.', $dim->position)
                );
            }
            if ($dim->enabled) {
                $positions[$dim->position] = true;
            }
        }

        $this->dimensions = $dimensions;
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, associative: true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON format for ShelfDimensionConfig.');
        }

        $dimensions = array_map(
            fn ($item) => ShelfDimensionMetadata::fromArray($item),
            $data
        );

        return new self($dimensions);
    }

    public function toJson(): string
    {
        $data = array_map(
            fn ($dim) => $dim->toArray(),
            $this->dimensions
        );

        $encoded = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($encoded === false) {
            throw new InvalidArgumentException('Failed to encode ShelfDimensionConfig to JSON.');
        }

        return $encoded;
    }

    /**
     * @return list<ShelfDimensionMetadata>
     */
    public function getEnabledDimensions(): array
    {
        return array_values(array_filter(
            $this->dimensions,
            fn ($dim) => $dim->enabled
        ));
    }

    public function getDimensionCount(): int
    {
        return count($this->getEnabledDimensions());
    }
}
