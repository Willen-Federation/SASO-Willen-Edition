<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;

/**
 * MCP tool: `manage_storage_location`
 *
 * Unified create / update / delete for storage locations (warehouse
 * zones, shelves, bins, etc.). Pass `action` to select the operation.
 *
 * create — inserts a new location. `code` must be uppercase alphanumeric
 *           + hyphen segments (e.g. WH1-A-03). `parentId` is optional;
 *           depth is computed automatically.
 * update — updates name, code, and/or position of an existing location.
 * delete — removes a location; children's parent_id becomes null.
 *
 * Scope: `items:write`.
 */
final class ManageStorageLocationTool implements McpTool
{
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
        return 'Create, update, or delete a storage location (warehouse zone, shelf, bin). Pass action: create | update | delete.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['action'],
            'properties' => [
                'action'   => [
                    'type'        => 'string',
                    'enum'        => ['create', 'update', 'delete'],
                    'description' => 'Operation to perform.',
                ],
                'id'       => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Location ID. Required for update and delete.',
                ],
                'code'     => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 120,
                    'description' => 'Location code — uppercase alphanumeric segments joined by hyphens (e.g. WH1-A-03). Required for create.',
                ],
                'name'     => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                    'description' => 'Human-readable label. Required for create.',
                ],
                'parentId' => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 1,
                    'description' => 'Parent location ID. null = root location.',
                ],
                'position' => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Sibling sort order (default 0).',
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
            throw new InvalidArgumentException('"code" must be uppercase alphanumeric segments joined by hyphens: '.$e->getMessage());
        }

        $depth = 0;
        if ($parentId !== null) {
            $parent = $this->locations->findById($parentId);
            if ($parent === null) {
                throw new InvalidArgumentException(sprintf('Parent location %d does not exist.', $parentId));
            }
            $depth = $parent->depth + 1;
        }

        $now  = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO storage_location (parent_id, code, name, position, depth, created_at, updated_at) '.
            'VALUES (:parent, :code, :name, :pos, :depth, :ca, :ua)',
        );
        $stmt->bindValue('parent', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('code', $code->toString());
        $stmt->bindValue('name', $name);
        $stmt->bindValue('pos', $position, PDO::PARAM_INT);
        $stmt->bindValue('depth', $depth, PDO::PARAM_INT);
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
            $newCodeStr = trim((string) $input['code']);
            try {
                $code = new LocationCode($newCodeStr);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('"code" must be uppercase alphanumeric + hyphens: '.$e->getMessage());
            }
        }

        $updated = new StorageLocation(
            id: $location->id,
            parentId: $location->parentId,
            code: $code,
            name: $name,
            position: $position,
            depth: $location->depth,
            createdAt: $location->createdAt,
            updatedAt: $location->updatedAt,
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
            'id'       => $loc->id,
            'parentId' => $loc->parentId,
            'code'     => $loc->code->toString(),
            'name'     => $loc->name,
            'position' => $loc->position,
            'depth'    => $loc->depth,
        ];
    }
}
