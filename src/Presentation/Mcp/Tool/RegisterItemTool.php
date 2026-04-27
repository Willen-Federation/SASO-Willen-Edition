<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `register_item`
 *
 * Creates a new item in the legacy `item` table.
 * Scope: `items:write` — write-capable device tokens only.
 */
final class RegisterItemTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'register_item';
    }

    public function description(): string
    {
        return 'Register a new inventory item. Requires the items:write scope.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['name', 'categoryId'],
            'properties' => [
                'name' => [
                    'type'        => 'string',
                    'description' => 'Item name.',
                    'minLength'   => 1,
                    'maxLength'   => 255,
                ],
                'categoryId' => [
                    'type'        => 'integer',
                    'description' => 'Category ID the item belongs to.',
                    'minimum'     => 1,
                ],
                'price' => [
                    'type'        => 'integer',
                    'description' => 'Price in smallest currency unit (e.g. yen). Defaults to 0.',
                    'minimum'     => 0,
                ],
                'janCode' => [
                    'type'        => ['string', 'null'],
                    'description' => 'JAN / EAN-13 barcode string.',
                    'maxLength'   => 64,
                ],
                'stock' => [
                    'type'        => 'integer',
                    'description' => 'Initial stock count. Defaults to 0.',
                    'minimum'     => 0,
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $name       = trim((string) ($input['name'] ?? ''));
        $categoryId = (int) ($input['categoryId'] ?? 0);
        $price      = max(0, (int) ($input['price'] ?? 0));
        $janCode    = isset($input['janCode']) ? trim((string) $input['janCode']) : null;
        $stock      = max(0, (int) ($input['stock'] ?? 0));
        $now        = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        if ($name === '' || $categoryId < 1) {
            throw new \InvalidArgumentException('"name" and "categoryId" are required.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO item (name, category_id, price, jan_code, stock, created_at, updated_at) '.
            'VALUES (:name, :category_id, :price, :jan_code, :stock, :created_at, :updated_at)',
        );
        $stmt->bindValue('name', $name);
        $stmt->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue('price', $price, PDO::PARAM_INT);
        $stmt->bindValue('jan_code', $janCode === '' ? null : $janCode);
        $stmt->bindValue('stock', $stock, PDO::PARAM_INT);
        $stmt->bindValue('created_at', $now);
        $stmt->bindValue('updated_at', $now);
        $stmt->execute();

        $newId = (int) $this->pdo->lastInsertId();

        return [
            'id'         => $newId,
            'name'       => $name,
            'categoryId' => $categoryId,
            'price'      => $price,
            'stock'      => $stock,
            'createdAt'  => $now,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }
}
