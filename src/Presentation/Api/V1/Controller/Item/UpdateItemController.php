<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Item;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Application\Common\IdempotencyService;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Saso\Presentation\Api\V1\Response\ProblemResponse;

/**
 * PATCH /api/v1/items/{id}
 *
 * Partial update — only keys present in the JSON body are changed.
 * Accepts same fields as POST /api/v1/items except name is optional here.
 *
 * Header: Idempotency-Key (recommended for retry safety)
 */
final class UpdateItemController
{
    /**
     * Permitted values for the `status` column. Mirrors the enum in
     * `config/openapi.yaml#/components/schemas/ItemResource.status` and the
     * legacy admin form in `item/template/changeStatus.php`. Any value here
     * is reachable from any other value — there are no transition rules.
     */
    private const ALLOWED_STATUSES = [
        'active',
        'archived',
        'discontinued',
        'pending',
        'in_storage',
        'in_use',
        'for_sale',
        'reserved',
        'shipped',
    ];

    // Application-side bounds keep oversize input from reaching MariaDB,
    // which silently truncates when sql_mode does not include STRICT_*
    // (the default on many shared-hosting installs). Values mirror the
    // column limits set by the `item` migrations.
    private const MAX_NAME_LENGTH       = 255;
    private const MAX_JAN_LENGTH        = 32;
    private const MAX_ISBN_LENGTH       = 32;
    private const MAX_LABEL_CODE_LENGTH = 64;

    public function __construct(
        private readonly PDO $pdo,
        private readonly JwtGuard $guard,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->requireScope($request, 'items:write');

        $id = (int) ($request->pathParams['id'] ?? 0);
        if ($id < 1) {
            throw self::invalid('Invalid item ID.');
        }

        $idempotencyKey = $request->header('idempotency-key');
        if ($idempotencyKey !== null) {
            $cached = $this->idempotency->lookup($idempotencyKey);
            if ($cached !== null) {
                return new JsonResponse(status: 200, body: $cached);
            }
        }

        $body  = $this->parseBody($request);
        $sets  = [];
        $binds = [];

        if (array_key_exists('name', $body)) {
            $name = trim((string) $body['name']);
            if ($name === '') {
                throw self::invalid('name must not be empty.');
            }
            if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
                throw self::invalid(sprintf('name must be at most %d characters.', self::MAX_NAME_LENGTH));
            }
            $sets[]        = 'name = :name';
            $binds['name'] = $name;
        }

        if (array_key_exists('categoryId', $body)) {
            $cat = (int) $body['categoryId'];
            if ($cat < 1) {
                throw self::invalid('categoryId must be a positive integer.');
            }
            $sets[]               = 'category_id = :category_id';
            $binds['category_id'] = $cat;
        }

        if (array_key_exists('janCode', $body)) {
            $jan = $body['janCode'] !== null ? trim((string) $body['janCode']) : null;
            if ($jan !== null && mb_strlen($jan) > self::MAX_JAN_LENGTH) {
                throw self::invalid(sprintf('janCode must be at most %d characters.', self::MAX_JAN_LENGTH));
            }
            $sets[]            = 'jan_code = :jan_code';
            $binds['jan_code'] = $jan === '' ? null : $jan;
        }

        if (array_key_exists('isbnCode', $body)) {
            $isbn = $body['isbnCode'] !== null ? trim((string) $body['isbnCode']) : null;
            if ($isbn !== null && mb_strlen($isbn) > self::MAX_ISBN_LENGTH) {
                throw self::invalid(sprintf('isbnCode must be at most %d characters.', self::MAX_ISBN_LENGTH));
            }
            $sets[]        = 'isbn = :isbn';
            $binds['isbn'] = $isbn === '' ? null : $isbn;
        }

        if (array_key_exists('labelCode', $body)) {
            $label = $body['labelCode'] !== null ? trim((string) $body['labelCode']) : null;
            if ($label !== null && mb_strlen($label) > self::MAX_LABEL_CODE_LENGTH) {
                throw self::invalid(sprintf('labelCode must be at most %d characters.', self::MAX_LABEL_CODE_LENGTH));
            }
            $sets[]              = 'label_code = :label_code';
            $binds['label_code'] = $label === '' ? null : $label;
        }

        if (array_key_exists('note', $body)) {
            $note          = $body['note'] !== null ? trim((string) $body['note']) : null;
            $sets[]        = 'note = :note';
            $binds['note'] = ($note === null || $note === '') ? null : mb_substr($note, 0, 255);
        }

        if (array_key_exists('price', $body)) {
            $sets[]         = 'price = :price';
            $binds['price'] = max(0, (int) $body['price']);
        }

        if (array_key_exists('stock', $body)) {
            $sets[]         = 'stock = :stock';
            $binds['stock'] = max(0, (int) $body['stock']);
        }

        if (array_key_exists('status', $body)) {
            $status = is_string($body['status']) ? $body['status'] : '';
            if (!in_array($status, self::ALLOWED_STATUSES, true)) {
                return ProblemResponse::unprocessable(
                    ErrorCode::ItemInvalidStatus->value,
                    'status must be one of: '.implode(', ', self::ALLOWED_STATUSES),
                );
            }
            $sets[]          = 'status = :status';
            $binds['status'] = $status;
        }

        if ($sets === []) {
            throw self::invalid('At least one field must be provided.');
        }

        $now                 = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $sets[]              = 'updated_at = :updated_at';
        $binds['updated_at'] = $now;
        $binds['id']         = $id;

        $stmt = $this->pdo->prepare('UPDATE item SET '.implode(', ', $sets).' WHERE id = :id');
        $stmt->execute($binds);

        if ($stmt->rowCount() === 0) {
            throw self::notFound();
        }

        $fetchStmt = $this->pdo->prepare(
            'SELECT i.id, i.name, i.category_id, c.name_ja AS category_name, '.
            'i.jan_code, i.isbn, i.label_code, i.note, i.stock, i.price, i.status, i.storage_location_id, '.
            'i.created_at, i.updated_at '.
            'FROM item i '.
            'LEFT JOIN category c ON c.id = i.category_id '.
            'WHERE i.id = :id LIMIT 1',
        );
        $fetchStmt->execute(['id' => $id]);
        $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        $responseBody = $row !== false ? $this->serialize($row) : ['id' => (string) $id];

        if ($idempotencyKey !== null) {
            $this->idempotency->store($idempotencyKey, $responseBody);
        }

        return new JsonResponse(status: 200, body: $responseBody);
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
            'labelCode'         => isset($row['label_code']) && $row['label_code'] !== null ? (string) $row['label_code'] : null,
            'note'              => isset($row['note']) && $row['note'] !== null ? (string) $row['note'] : null,
            'price'             => isset($row['price']) ? (int) $row['price'] : 0,
            'stock'             => isset($row['stock']) ? (int) $row['stock'] : 0,
            'status'            => (string) ($row['status'] ?? 'active'),
            'storageLocationId' => isset($row['storage_location_id']) ? (string) $row['storage_location_id'] : null,
            'features'          => [],
            'registeredAt'      => (string) ($row['created_at'] ?? ''),
            'updatedAt'         => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function parseBody(HttpRequest $request): array
    {
        if ($request->body === null || $request->body === '') {
            return [];
        }
        $decoded = json_decode($request->body, associative: true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function invalid(string $message): DomainException
    {
        return new class ($message) extends DomainException {
            public function __construct(string $msg)
            {
                parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
            }
        };
    }

    private static function notFound(): DomainException
    {
        return new class ('Item not found.') extends DomainException {
            public function __construct(string $msg)
            {
                parent::__construct(ErrorCode::ItemNotFound, $msg);
            }
        };
    }
}
