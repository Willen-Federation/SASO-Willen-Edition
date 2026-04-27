<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use PDO;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\LocationType;
use Saso\Domain\StorageLocation\StorageOperationalStatus;

/**
 * MCP tool: `get_storage_location_tree`
 *
 * Returns storage locations as a nested JSON tree. Each node contains
 * all detail fields plus a `children` array of its direct children,
 * recursively. Pass `rootId` to retrieve only the subtree under that
 * node; omit it to get the full forest (all roots).
 *
 * Scope: none — any authenticated device can read.
 */
final class GetStorageLocationTreeTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'get_storage_location_tree';
    }

    public function description(): string
    {
        return 'Return storage locations as a nested tree (children arrays). Omit rootId for the full forest; pass rootId to get a specific subtree. Each node includes operational status (入出庫・キープ・出庫禁止等) and all detail fields.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'rootId' => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 1,
                    'description' => 'Root node ID to retrieve as subtree. Omit for the full forest.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $rootId = isset($input['rootId']) && $input['rootId'] !== null
            ? (int) $input['rootId']
            : null;

        $allRows = $this->fetchAll();

        /** @var array<int, array<string, mixed>> $byId */
        $byId = [];
        foreach ($allRows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        if ($rootId !== null) {
            if (!isset($byId[$rootId])) {
                return ['tree' => null, 'total' => 0];
            }

            $node  = $this->buildNode($rootId, $byId);
            $count = $this->countNodes($node);

            return ['tree' => $node, 'total' => $count];
        }

        $roots = array_filter($allRows, static fn (array $r): bool => $r['parent_id'] === null);
        $trees = array_values(array_map(
            fn (array $r): array => $this->buildNode((int) $r['id'], $byId),
            $roots,
        ));

        $total = array_sum(array_map($this->countNodes(...), $trees));

        return ['tree' => $trees, 'total' => $total];
    }

    public function requiredScope(): ?string
    {
        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM storage_location ORDER BY depth ASC, position ASC, id ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int, array<string, mixed>> $byId
     *
     * @return array<string, mixed>
     */
    private function buildNode(int $id, array $byId): array
    {
        $row = $byId[$id];

        $locationType = isset($row['location_type']) && is_string($row['location_type'])
            ? (LocationType::tryFrom($row['location_type']) ?? LocationType::Bin)
            : LocationType::Bin;

        $operationalStatus = isset($row['operational_status']) && is_string($row['operational_status'])
            ? (StorageOperationalStatus::tryFrom($row['operational_status']) ?? StorageOperationalStatus::Available)
            : StorageOperationalStatus::Available;

        $children = array_values(array_filter(
            $byId,
            static fn (array $r): bool => $r['parent_id'] !== null && (int) $r['parent_id'] === $id,
        ));

        usort($children, static function (array $a, array $b): int {
            $posDiff = (int) $a['position'] - (int) $b['position'];

            return $posDiff !== 0 ? $posDiff : (int) $a['id'] - (int) $b['id'];
        });

        $childNodes = array_map(
            fn (array $child): array => $this->buildNode((int) $child['id'], $byId),
            $children,
        );

        $capacity = isset($row['capacity']) && $row['capacity'] !== null ? (int) $row['capacity'] : null;

        return [
            'id'               => (int) $row['id'],
            'parentId'         => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'code'             => (new LocationCode((string) $row['code']))->toString(),
            'name'             => (string) $row['name'],
            'locationType'     => $locationType->value,
            'locationTypeLabel' => [
                'en' => $locationType->labelEn(),
                'ja' => $locationType->labelJa(),
            ],
            'position'          => (int) $row['position'],
            'depth'             => (int) $row['depth'],
            'description'       => isset($row['description']) && is_string($row['description']) ? $row['description'] : null,
            'capacity'          => $capacity,
            'notes'             => isset($row['notes']) && is_string($row['notes']) ? $row['notes'] : null,
            'operationalStatus' => $operationalStatus->value,
            'operationalStatusLabel' => [
                'en' => $operationalStatus->labelEn(),
                'ja' => $operationalStatus->labelJa(),
            ],
            'canReceive' => $operationalStatus->canReceive(),
            'canShip'    => $operationalStatus->canShip(),
            'children'   => $childNodes,
        ];
    }

    /** @param array<string, mixed> $node */
    private function countNodes(array $node): int
    {
        $count = 1;
        foreach ($node['children'] as $child) {
            $count += $this->countNodes($child);
        }

        return $count;
    }
}
