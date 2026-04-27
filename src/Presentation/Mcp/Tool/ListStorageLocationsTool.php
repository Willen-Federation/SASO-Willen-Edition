<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Mcp\McpTool;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;

/**
 * MCP tool: `list_storage_locations`
 *
 * Returns the root storage locations (warehouse zones). Use `parentId`
 * filtering in a follow-up call to drill down into sub-locations.
 * Scope: none — any authenticated device can read.
 */
final class ListStorageLocationsTool implements McpTool
{
    public function __construct(
        private readonly StorageLocationRepository $locations,
    ) {
    }

    public function name(): string
    {
        return 'list_storage_locations';
    }

    public function description(): string
    {
        return 'List root storage locations (warehouse zones). Returns id, code, name, and depth.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'parentId' => [
                    'type'        => ['integer', 'null'],
                    'description' => 'Parent location ID to list children of, or null for roots.',
                    'minimum'     => 1,
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $parentId = isset($input['parentId']) && $input['parentId'] !== null
            ? (int) $input['parentId']
            : null;

        $list = $parentId !== null
            ? $this->locations->listChildrenOf($parentId)
            : $this->locations->listRoots();

        return [
            'locations' => array_map(self::serialize(...), $list),
            'total'     => count($list),
        ];
    }

    public function requiredScope(): ?string
    {
        return null;
    }

    /** @return array<string, mixed> */
    private static function serialize(StorageLocation $loc): array
    {
        return [
            'id'               => $loc->id,
            'parentId'         => $loc->parentId,
            'code'             => $loc->code->toString(),
            'name'             => $loc->name,
            'locationType'     => $loc->locationType->value,
            'locationTypeLabel' => [
                'en' => $loc->locationType->labelEn(),
                'ja' => $loc->locationType->labelJa(),
            ],
            'position'          => $loc->position,
            'depth'             => $loc->depth,
            'description'       => $loc->description,
            'capacity'          => $loc->capacity,
            'notes'             => $loc->notes,
            'operationalStatus' => $loc->operationalStatus->value,
            'operationalStatusLabel' => [
                'en' => $loc->operationalStatus->labelEn(),
                'ja' => $loc->operationalStatus->labelJa(),
            ],
            'canReceive' => $loc->operationalStatus->canReceive(),
            'canShip'    => $loc->operationalStatus->canShip(),
        ];
    }
}
