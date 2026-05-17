<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Item;

use PDO;
use Saso\Application\Mobile\JwtGuard;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/items
 *
 * Query parameters:
 *   q           string   keyword search (LIKE %)
 *   category_id int      filter by category
 *   barcode     string   exact JAN/EAN code match
 *   isbn        string   exact ISBN-13 code match
 *   label_code  string   exact custom label/shelf code match
 *   limit       int      default 20, max 200
 *   cursor      int      last seen item ID for cursor pagination
 */
final class ListItemsController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly JwtGuard $guard,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->requireScope($request, 'items:read');

        $limit = min(200, max(1, (int) ($request->query['limit'] ?? 20)));
        $cursor = isset($request->query['cursor']) && $request->query['cursor'] !== ''
            ? (int) $request->query['cursor']
            : null;
        $q = isset($request->query['q']) && $request->query['q'] !== ''
            ? trim($request->query['q'])
            : null;
        $categoryId = isset($request->query['category_id']) && $request->query['category_id'] !== ''
            ? (int) $request->query['category_id']
            : null;
        $barcode = isset($request->query['barcode']) && $request->query['barcode'] !== ''
            ? trim($request->query['barcode'])
            : null;
        $isbn = isset($request->query['isbn']) && $request->query['isbn'] !== ''
            ? trim($request->query['isbn'])
            : null;
        $labelCode = isset($request->query['label_code']) && $request->query['label_code'] !== ''
            ? trim($request->query['label_code'])
            : null;

        $where = ['1=1'];
        $binds = [];

        if ($cursor !== null) {
            $where[]         = 'i.id > :cursor';
            $binds['cursor'] = $cursor;
        }

        if ($q !== null) {
            $where[]   = '(i.name LIKE :q OR i.jan_code LIKE :q OR i.isbn LIKE :q)';
            $binds['q'] = '%'.$q.'%';
        }

        if ($categoryId !== null) {
            $where[]               = 'i.category_id = :category_id';
            $binds['category_id'] = $categoryId;
        }

        if ($barcode !== null) {
            $where[]            = 'i.jan_code = :barcode';
            $binds['barcode'] = $barcode;
        }

        if ($isbn !== null) {
            $where[]         = 'i.isbn = :isbn';
            $binds['isbn'] = $isbn;
        }

        if ($labelCode !== null) {
            $where[]               = 'i.label_code = :label_code';
            $binds['label_code'] = $labelCode;
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM item i WHERE {$whereClause}",
        );
        $countStmt->execute($binds);
        $total = (int) $countStmt->fetchColumn();

        $binds['limit'] = $limit + 1;
        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.name, i.category_id, c.name_ja AS category_name, '.
            'i.jan_code, i.isbn, i.label_code, i.stock, i.price, i.status, i.storage_location_id, '.
            'i.created_at, i.updated_at '.
            'FROM item i '.
            'LEFT JOIN category c ON c.id = i.category_id '.
            "WHERE {$whereClause} ".
            'ORDER BY i.id ASC '.
            'LIMIT :limit',
        );
        $stmt->execute($binds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_pop($rows);
        }

        $nextCursor = $hasMore && !empty($rows)
            ? (int) end($rows)['id']
            : null;

        return new JsonResponse(
            status: 200,
            body: [
                'data'       => array_map(self::serialize(...), $rows),
                'total'      => $total,
                'nextCursor' => $nextCursor,
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
            'isbnCode'          => isset($row['isbn']) && $row['isbn'] !== null ? (string) $row['isbn'] : null,
            'labelCode'         => isset($row['label_code']) && $row['label_code'] !== null ? (string) $row['label_code'] : null,
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
