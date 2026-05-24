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

/**
 * POST /api/v1/items
 *
 * Body (JSON):
 *   name        string  required
 *   categoryId  int     required
 *   janCode     string  optional
 *   isbnCode    string  optional
 *   labelCode   string  optional
 *   note        string  optional — free-form remarks, max 255 chars
 *   price       int     optional, default 0
 *   stock       int     optional, default 0
 *
 * Header: Idempotency-Key (recommended for retry safety)
 */
final class CreateItemController
{
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

        $idempotencyKey = $request->header('idempotency-key');
        if ($idempotencyKey !== null) {
            $cached = $this->idempotency->lookup($idempotencyKey);
            if ($cached !== null) {
                return new JsonResponse(status: 200, body: $cached);
            }
        }

        $body = $this->parseBody($request);
        $name = trim((string) ($body['name'] ?? ''));
        $categoryId = (int) ($body['categoryId'] ?? 0);

        if ($name === '') {
            throw self::invalid('name is required.');
        }
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw self::invalid(sprintf('name must be at most %d characters.', self::MAX_NAME_LENGTH));
        }
        if ($categoryId < 1) {
            throw self::invalid('categoryId must be a positive integer.');
        }

        $janCode   = isset($body['janCode']) && $body['janCode'] !== '' ? trim((string) $body['janCode']) : null;
        $isbnCode  = isset($body['isbnCode']) && $body['isbnCode'] !== '' ? trim((string) $body['isbnCode']) : null;
        $labelCode = isset($body['labelCode']) && $body['labelCode'] !== '' ? trim((string) $body['labelCode']) : null;
        $note      = isset($body['note']) && $body['note'] !== '' ? mb_substr(trim((string) $body['note']), 0, 255) : null;

        if ($janCode !== null && mb_strlen($janCode) > self::MAX_JAN_LENGTH) {
            throw self::invalid(sprintf('janCode must be at most %d characters.', self::MAX_JAN_LENGTH));
        }
        if ($isbnCode !== null && mb_strlen($isbnCode) > self::MAX_ISBN_LENGTH) {
            throw self::invalid(sprintf('isbnCode must be at most %d characters.', self::MAX_ISBN_LENGTH));
        }
        if ($labelCode !== null && mb_strlen($labelCode) > self::MAX_LABEL_CODE_LENGTH) {
            throw self::invalid(sprintf('labelCode must be at most %d characters.', self::MAX_LABEL_CODE_LENGTH));
        }
        $price   = max(0, (int) ($body['price'] ?? 0));
        $stock   = max(0, (int) ($body['stock'] ?? 0));
        $now     = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'INSERT INTO item (name, category_id, jan_code, isbn, label_code, note, price, stock, status, created_at, updated_at) '.
            'VALUES (:name, :category_id, :jan_code, :isbn, :label_code, :note, :price, :stock, :status, :created_at, :updated_at)',
        );
        $stmt->bindValue('name', $name);
        $stmt->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue('jan_code', $janCode);
        $stmt->bindValue('isbn', $isbnCode);
        $stmt->bindValue('label_code', $labelCode);
        $stmt->bindValue('note', $note);
        $stmt->bindValue('price', $price, PDO::PARAM_INT);
        $stmt->bindValue('stock', $stock, PDO::PARAM_INT);
        $stmt->bindValue('status', 'active');
        $stmt->bindValue('created_at', $now);
        $stmt->bindValue('updated_at', $now);
        $stmt->execute();

        $newId = (int) $this->pdo->lastInsertId();

        $catStmt = $this->pdo->prepare('SELECT name_ja FROM category WHERE id = :id LIMIT 1');
        $catStmt->execute(['id' => $categoryId]);
        $catName = $catStmt->fetchColumn();

        $responseBody = [
            'id'                => (string) $newId,
            'name'              => $name,
            'description'       => null,
            'categoryId'        => (string) $categoryId,
            'categoryName'      => $catName !== false ? (string) $catName : null,
            'janCode'           => $janCode,
            'isbnCode'          => $isbnCode,
            'labelCode'         => $labelCode,
            'note'              => $note,
            'price'             => $price,
            'stock'             => $stock,
            'status'            => 'active',
            'storageLocationId' => null,
            'features'          => [],
            'registeredAt'      => $now,
            'updatedAt'         => $now,
        ];

        if ($idempotencyKey !== null) {
            $this->idempotency->store($idempotencyKey, $responseBody);
        }

        return new JsonResponse(status: 201, body: $responseBody);
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
}
