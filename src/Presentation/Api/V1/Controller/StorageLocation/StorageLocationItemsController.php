<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\StorageLocation;

use PDO;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/storage-locations/{id}/items
 *
 * Returns items currently assigned to a storage location.
 *
 * Query parameters:
 *   limit   int  default 50, max 200
 */
final class StorageLocationItemsController
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
            throw new class ('Invalid storage location ID.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

        $limit = min(200, max(1, (int) ($request->query['limit'] ?? 50)));

        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.name, i.category_id, c.name_ja AS category_name, '.
            'i.jan_code, i.stock, i.price, i.status, i.storage_location_id, '.
            'i.created_at, i.updated_at '.
            'FROM item i '.
            'LEFT JOIN category c ON c.id = i.category_id '.
            'WHERE i.storage_location_id = :loc_id '.
            'ORDER BY i.id ASC '.
            'LIMIT :limit',
        );
        $stmt->bindValue('loc_id', $id, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return new JsonResponse(
            status: 200,
            body: [
                'data'  => array_map(self::serialize(...), $rows),
                'total' => count($rows),
            ],
        );
    }

    /** @param array<string, mixed> $row */
    private static function serialize(array $row): array
    {
        return [
            'id'                => (string) $row['id'],
            'name'              => (string) ($row['name'] ?? ''),
            'description'       => null,
            'categoryId'        => (string) ($row['category_id'] ?? ''),
            'categoryName'      => isset($row['category_name']) ? (string) $row['category_name'] : null,
            'janCode'           => isset($row['jan_code']) && $row['jan_code'] !== null ? (string) $row['jan_code'] : null,
            'price'             => isset($row['price']) ? (int) $row['price'] : 0,
            'stock'             => isset($row['stock']) ? (int) $row['stock'] : 0,
            'status'            => (string) ($row['status'] ?? 'active'),
            'storageLocationId' => isset($row['storage_location_id']) ? (string) $row['storage_location_id'] : null,
            'features'          => [],
            'registeredAt'      => (string) ($row['created_at'] ?? ''),
            'updatedAt'         => isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        ];
    }
}
