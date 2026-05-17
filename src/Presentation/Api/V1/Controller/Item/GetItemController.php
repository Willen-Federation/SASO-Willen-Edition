<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Item;

use PDO;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/items/{id}
 *
 * Returns a single item with its attribute values and category name.
 */
final class GetItemController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly JwtGuard $guard,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->requireScope($request, 'items:read');

        $id = (int) ($request->pathParams['id'] ?? 0);
        if ($id < 1) {
            throw new class ('Invalid item ID.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.name, i.category_id, c.name_ja AS category_name, '.
            'i.jan_code, i.isbn, i.label_code, i.stock, i.price, i.status, i.storage_location_id, '.
            'i.created_at, i.updated_at '.
            'FROM item i '.
            'LEFT JOIN category c ON c.id = i.category_id '.
            'WHERE i.id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new class ('Item not found.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::ItemNotFound, $msg);
                }
            };
        }

        // Load attribute values
        $attrStmt = $this->pdo->prepare(
            'SELECT d.key, d.label_ja, v.value_text, v.value_int, v.value_bool '.
            'FROM item_attribute_value v '.
            'JOIN item_attribute_definition d ON d.id = v.definition_id '.
            'WHERE v.item_id = :id',
        );
        $attrStmt->execute(['id' => $id]);
        $attrs = $attrStmt->fetchAll(PDO::FETCH_ASSOC);

        return new JsonResponse(
            status: 200,
            body: array_merge(
                $this->serialize($row),
                ['attributes' => array_map(self::serializeAttr(...), $attrs)],
            ),
        );
    }

    /** @param array<string, mixed> $row */
    private function serialize(array $row): array
    {
        return [
            'id'                => (string) $row['id'],
            'name'              => (string) ($row['name'] ?? ''),
            'description'       => null,
            'categoryId'        => (string) ($row['category_id'] ?? ''),
            'categoryName'      => isset($row['category_name']) ? (string) $row['category_name'] : null,
            'janCode'           => isset($row['jan_code']) && $row['jan_code'] !== null ? (string) $row['jan_code'] : null,
            'isbnCode'          => isset($row['isbn']) && $row['isbn'] !== null ? (string) $row['isbn'] : null,
            'labelCode'         => isset($row['label_code']) ? (string) $row['label_code'] : null,
            'price'             => isset($row['price']) ? (int) $row['price'] : 0,
            'stock'             => isset($row['stock']) ? (int) $row['stock'] : 0,
            'status'            => (string) ($row['status'] ?? 'active'),
            'storageLocationId' => isset($row['storage_location_id']) ? (string) $row['storage_location_id'] : null,
            'features'          => [],
            'registeredAt'      => (string) ($row['created_at'] ?? ''),
            'updatedAt'         => isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        ];
    }

    /** @param array<string, mixed> $attr */
    private static function serializeAttr(array $attr): array
    {
        $value = $attr['value_text'] ?? $attr['value_int'] ?? $attr['value_bool'] ?? null;

        return [
            'key'     => (string) ($attr['key'] ?? ''),
            'label'   => (string) ($attr['label_ja'] ?? ''),
            'value'   => $value,
        ];
    }
}
