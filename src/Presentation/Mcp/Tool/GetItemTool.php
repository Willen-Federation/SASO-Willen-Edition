<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use PDO;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `get_item`
 *
 * Looks up a single item by ID from the legacy `item` table.
 * Scope: none — any authenticated device can read.
 */
final class GetItemTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'get_item';
    }

    public function description(): string
    {
        return 'Retrieve a single inventory item by its numeric ID.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['id'],
            'properties' => [
                'id' => [
                    'type'        => 'integer',
                    'description' => 'The item ID.',
                    'minimum'     => 1,
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            return ['item' => null, 'found' => false];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, name, price, category_id, jan_code, stock, created_at '.
            'FROM item WHERE id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return ['item' => null, 'found' => false];
        }

        return [
            'item'  => $this->serialize($row),
            'found' => true,
        ];
    }

    public function requiredScope(): ?string
    {
        return null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'name'        => (string) ($row['name'] ?? ''),
            'price'       => isset($row['price']) ? (int) $row['price'] : null,
            'categoryId'  => isset($row['category_id']) ? (int) $row['category_id'] : null,
            'janCode'     => isset($row['jan_code']) ? (string) $row['jan_code'] : null,
            'stock'       => isset($row['stock']) ? (int) $row['stock'] : null,
            'createdAt'   => (string) ($row['created_at'] ?? ''),
        ];
    }
}
