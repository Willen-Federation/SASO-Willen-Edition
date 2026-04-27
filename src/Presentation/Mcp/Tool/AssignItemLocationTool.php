<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `assign_item_location`
 *
 * Assigns an item to a storage location, or clears the assignment by
 * passing `locationId: null`. Requires the `storage_location_id` column
 * added by migration M6-I-004. Scope: `items:write`.
 */
final class AssignItemLocationTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'assign_item_location';
    }

    public function description(): string
    {
        return 'Assign an inventory item to a storage location, or unassign it by passing locationId: null.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['id', 'locationId'],
            'properties' => [
                'id'         => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Item ID.',
                ],
                'locationId' => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 1,
                    'description' => 'Storage location ID to assign, or null to clear the assignment.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('"id" must be a positive integer.');
        }

        $locationId = null;
        if (array_key_exists('locationId', $input) && $input['locationId'] !== null) {
            $locationId = (int) $input['locationId'];
            if ($locationId < 1) {
                throw new InvalidArgumentException('"locationId" must be a positive integer or null.');
            }

            $check = $this->pdo->prepare('SELECT id FROM storage_location WHERE id = :lid LIMIT 1');
            $check->execute(['lid' => $locationId]);
            if ($check->fetch() === false) {
                throw new InvalidArgumentException(
                    sprintf('Storage location %d does not exist.', $locationId),
                );
            }
        }

        $now  = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE item SET storage_location_id = :lid, updated_at = :ua WHERE id = :id',
        );
        $stmt->bindValue('lid', $locationId, $locationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('ua', $now);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'updated'    => $stmt->rowCount() > 0,
            'id'         => $id,
            'locationId' => $locationId,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }
}
