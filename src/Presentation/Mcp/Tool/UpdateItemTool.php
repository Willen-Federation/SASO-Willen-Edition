<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `update_item`
 *
 * Performs a partial update on an existing item row. Only the fields
 * that are explicitly present in the `arguments` object are changed;
 * absent fields are left as-is. Scope: `items:write`.
 */
final class UpdateItemTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'update_item';
    }

    public function description(): string
    {
        return 'Partially update an existing inventory item. Only the supplied fields are changed.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['id'],
            'properties' => [
                'id'         => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'ID of the item to update.',
                ],
                'name'       => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 255,
                    'description' => 'New item name.',
                ],
                'price'      => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Price in smallest currency unit (e.g. yen).',
                ],
                'categoryId' => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'New category ID.',
                ],
                'janCode'    => [
                    'type'        => ['string', 'null'],
                    'maxLength'   => 64,
                    'description' => 'JAN/EAN-13 barcode string, or null to clear.',
                ],
                'stock'      => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Stock count.',
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

        $sets  = [];
        $binds = [];

        if (array_key_exists('name', $input)) {
            $name = trim((string) $input['name']);
            if ($name === '') {
                throw new InvalidArgumentException('"name" must not be empty.');
            }
            $sets[]        = 'name = :name';
            $binds['name'] = $name;
        }

        if (array_key_exists('price', $input)) {
            $sets[]         = 'price = :price';
            $binds['price'] = max(0, (int) $input['price']);
        }

        if (array_key_exists('categoryId', $input)) {
            $cat = (int) $input['categoryId'];
            if ($cat < 1) {
                throw new InvalidArgumentException('"categoryId" must be a positive integer.');
            }
            $sets[]               = 'category_id = :category_id';
            $binds['category_id'] = $cat;
        }

        if (array_key_exists('janCode', $input)) {
            $jan              = $input['janCode'] !== null ? trim((string) $input['janCode']) : null;
            $sets[]           = 'jan_code = :jan_code';
            $binds['jan_code'] = ($jan === '' ? null : $jan);
        }

        if (array_key_exists('stock', $input)) {
            $sets[]         = 'stock = :stock';
            $binds['stock'] = max(0, (int) $input['stock']);
        }

        if ($sets === []) {
            throw new InvalidArgumentException('At least one field to update must be provided.');
        }

        $sets[]              = 'updated_at = :updated_at';
        $binds['updated_at'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $binds['id']         = $id;

        $stmt = $this->pdo->prepare('UPDATE item SET '.implode(', ', $sets).' WHERE id = :id');
        $stmt->execute($binds);

        if ($stmt->rowCount() === 0) {
            return ['updated' => false, 'id' => $id];
        }

        $fetch = $this->pdo->prepare(
            'SELECT id, name, price, category_id, jan_code, stock, status, storage_location_id, created_at, updated_at '.
            'FROM item WHERE id = :id LIMIT 1',
        );
        $fetch->execute(['id' => $id]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);

        return [
            'updated' => true,
            'item'    => $row !== false ? $this->serialize($row) : null,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        return [
            'id'                => (int) $row['id'],
            'name'              => (string) ($row['name'] ?? ''),
            'price'             => isset($row['price']) ? (int) $row['price'] : null,
            'categoryId'        => isset($row['category_id']) ? (int) $row['category_id'] : null,
            'janCode'           => isset($row['jan_code']) ? (string) $row['jan_code'] : null,
            'stock'             => isset($row['stock']) ? (int) $row['stock'] : null,
            'status'            => (string) ($row['status'] ?? 'active'),
            'storageLocationId' => isset($row['storage_location_id']) ? (int) $row['storage_location_id'] : null,
            'createdAt'         => (string) ($row['created_at'] ?? ''),
            'updatedAt'         => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
