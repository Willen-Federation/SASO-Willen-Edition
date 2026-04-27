<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\LocationType;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;

/**
 * MCP tool: `manage_storage_location`
 *
 * Unified create / update / delete for storage locations. Pass `action`
 * to select the operation.
 *
 * Typical hierarchy (informational):
 *   Facility → Zone → Aisle → Rack → Shelf → Tier → Bin
 *   施設     → ゾーン → 通路  → ラック → 棚   → 段   → 棚区画
 *
 * create — inserts a new location. `code` (uppercase + hyphens, e.g.
 *           WH1-A-03-B12) and `name` are required. `parentId`,
 *           `locationType`, `description`, `capacity`, `notes`, and
 *           `position` are optional. Depth is computed from the parent.
 * update — updates any combination of code, name, locationType,
 *           description, capacity, notes, position for an existing node.
 * delete — removes the location; children's parent_id becomes null.
 *
 * Scope: `items:write`.
 */
final class ManageStorageLocationTool implements McpTool
{
    private const LOCATION_TYPES = ['facility', 'zone', 'aisle', 'rack', 'shelf', 'tier', 'bin'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly StorageLocationRepository $locations,
    ) {
    }

    public function name(): string
    {
        return 'manage_storage_location';
    }

    public function description(): string
    {
        return 'Create, update, or delete a storage location (facility/zone/aisle/rack/shelf/tier/bin). Supports detailed addressing: facility 施設, zone ゾーン, aisle 通路, rack ラック, shelf 棚, tier 段, bin 棚区画.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['action'],
            'properties' => [
                'action'       => [
                    'type'        => 'string',
                    'enum'        => ['create', 'update', 'delete'],
                    'description' => 'Operation to perform.',
                ],
                'id'           => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Location ID. Required for update and delete.',
                ],
                'code'         => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 120,
                    'description' => 'Barcode-friendly code — uppercase alphanumeric + hyphens (e.g. WH1-A-03-B12). Required for create.',
                ],
                'name'         => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                    'description' => 'Human-readable label (e.g. "北倉庫 3列目 棚B 2段目"). Required for create.',
                ],
                'locationType' => [
                    'type'        => 'string',
                    'enum'        => self::LOCATION_TYPES,
                    'description' => 'Physical type: facility(施設) | zone(ゾーン) | aisle(通路) | rack(ラック) | shelf(棚) | tier(段) | bin(棚区画, default).',
                ],
                'parentId'     => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 1,
                    'description' => 'Parent location ID for hierarchical placement. null = root.',
                ],
                'position'     => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Sibling sort order (default 0).',
                ],
                'description'  => [
                    'type'        => ['string', 'null'],
                    'description' => 'Free-text description of this location.',
                ],
                'capacity'     => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 0,
                    'description' => 'Maximum number of items this location can hold. null = unlimited.',
                ],
                'notes'        => [
                    'type'        => ['string', 'null'],
                    'description' => 'Operator notes (e.g. temperature zone, hazard flags, access restrictions).',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $action = (string) ($input['action'] ?? '');

        return match ($action) {
            'create' => $this->create($input),
            'update' => $this->update($input),
            'delete' => $this->delete($input),
            default  => throw new InvalidArgumentException(
                '"action" must be one of: create, update, delete.',
            ),
        };
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function create(array $input): array
    {
        $codeStr  = trim((string) ($input['code'] ?? ''));
        $name     = trim((string) ($input['name'] ?? ''));
        $parentId = isset($input['parentId']) && $input['parentId'] !== null
            ? (int) $input['parentId']
            : null;
        $position = max(0, (int) ($input['position'] ?? 0));

        if ($codeStr === '') {
            throw new InvalidArgumentException('"code" is required for create.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('"name" is required for create.');
        }

        try {
            $code = new LocationCode($codeStr);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                '"code" must be uppercase alphanumeric segments joined by hyphens (e.g. WH1-A-03): '.$e->getMessage(),
            );
        }

        $locationType = $this->parseLocationType($input);
        $description  = $this->parseNullableString($input, 'description');
        $capacity     = $this->parseNullableCapacity($input);
        $notes        = $this->parseNullableString($input, 'notes');

        $depth = 0;
        if ($parentId !== null) {
            $parent = $this->locations->findById($parentId);
            if ($parent === null) {
                throw new InvalidArgumentException(
                    sprintf('Parent location %d does not exist.', $parentId),
                );
            }
            $depth = $parent->depth + 1;
        }

        $now  = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO storage_location '.
            '(parent_id, code, name, position, depth, location_type, description, capacity, notes, created_at, updated_at) '.
            'VALUES (:parent, :code, :name, :pos, :depth, :ltype, :desc, :cap, :notes, :ca, :ua)',
        );
        $stmt->bindValue('parent', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('code', $code->toString());
        $stmt->bindValue('name', $name);
        $stmt->bindValue('pos', $position, PDO::PARAM_INT);
        $stmt->bindValue('depth', $depth, PDO::PARAM_INT);
        $stmt->bindValue('ltype', $locationType->value);
        $stmt->bindValue('desc', $description);
        $stmt->bindValue('cap', $capacity, $capacity === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('notes', $notes);
        $stmt->bindValue('ca', $now);
        $stmt->bindValue('ua', $now);
        $stmt->execute();

        $newId    = (int) $this->pdo->lastInsertId();
        $location = $this->locations->findById($newId);

        if ($location === null) {
            throw new \RuntimeException('Failed to read storage location after create.');
        }

        return ['action' => 'created', 'location' => $this->serialize($location)];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function update(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('"id" is required for update.');
        }

        $location = $this->locations->findById($id);
        if ($location === null) {
            throw new InvalidArgumentException(sprintf('Storage location %d does not exist.', $id));
        }

        $name     = array_key_exists('name', $input) ? trim((string) $input['name']) : $location->name;
        $position = array_key_exists('position', $input) ? max(0, (int) $input['position']) : $location->position;

        if ($name === '') {
            throw new InvalidArgumentException('"name" must not be empty.');
        }

        $code = $location->code;
        if (array_key_exists('code', $input)) {
            try {
                $code = new LocationCode(trim((string) $input['code']));
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('"code" must be uppercase alphanumeric + hyphens: '.$e->getMessage());
            }
        }

        $locationType = array_key_exists('locationType', $input)
            ? $this->parseLocationType($input)
            : $location->locationType;

        $description = array_key_exists('description', $input)
            ? $this->parseNullableString($input, 'description')
            : $location->description;

        $capacity = array_key_exists('capacity', $input)
            ? $this->parseNullableCapacity($input)
            : $location->capacity;

        $notes = array_key_exists('notes', $input)
            ? $this->parseNullableString($input, 'notes')
            : $location->notes;

        $updated = new StorageLocation(
            id: $location->id,
            parentId: $location->parentId,
            code: $code,
            name: $name,
            position: $position,
            depth: $location->depth,
            createdAt: $location->createdAt,
            updatedAt: $location->updatedAt,
            locationType: $locationType,
            description: $description,
            capacity: $capacity,
            notes: $notes,
        );

        $saved = $this->locations->save($updated);

        return ['action' => 'updated', 'location' => $this->serialize($saved)];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function delete(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('"id" is required for delete.');
        }

        $exists = $this->locations->findById($id);
        if ($exists === null) {
            throw new InvalidArgumentException(sprintf('Storage location %d does not exist.', $id));
        }

        $this->locations->delete($id);

        return ['action' => 'deleted', 'id' => $id];
    }

    /** @return array<string, mixed> */
    private function serialize(StorageLocation $loc): array
    {
        return [
            'id'           => $loc->id,
            'parentId'     => $loc->parentId,
            'code'         => $loc->code->toString(),
            'name'         => $loc->name,
            'locationType' => $loc->locationType->value,
            'locationTypeLabel' => [
                'en' => $loc->locationType->labelEn(),
                'ja' => $loc->locationType->labelJa(),
            ],
            'position'     => $loc->position,
            'depth'        => $loc->depth,
            'description'  => $loc->description,
            'capacity'     => $loc->capacity,
            'notes'        => $loc->notes,
        ];
    }

    /** @param array<string, mixed> $input */
    private function parseLocationType(array $input): LocationType
    {
        $raw = isset($input['locationType']) ? (string) $input['locationType'] : 'bin';
        $type = LocationType::tryFrom($raw);

        if ($type === null) {
            throw new InvalidArgumentException(
                sprintf('"locationType" must be one of: %s.', implode(', ', self::LOCATION_TYPES)),
            );
        }

        return $type;
    }

    /** @param array<string, mixed> $input */
    private function parseNullableString(array $input, string $key): ?string
    {
        if (!isset($input[$key])) {
            return null;
        }
        $val = trim((string) $input[$key]);

        return $val === '' ? null : $val;
    }

    /** @param array<string, mixed> $input */
    private function parseNullableCapacity(array $input): ?int
    {
        if (!isset($input['capacity'])) {
            return null;
        }
        $cap = (int) $input['capacity'];
        if ($cap < 0) {
            throw new InvalidArgumentException('"capacity" must be ≥ 0.');
        }

        return $cap;
    }
}
