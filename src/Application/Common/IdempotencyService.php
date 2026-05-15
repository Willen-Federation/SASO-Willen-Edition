<?php

declare(strict_types=1);

namespace Saso\Application\Common;

use PDO;

/**
 * Stores and retrieves idempotent responses keyed by Idempotency-Key header.
 *
 * Cached responses expire after 24 hours. On first call with a key the
 * caller stores the response body after it succeeds; on retry the stored
 * body is returned without re-executing the operation.
 *
 * Table: idempotency_key (key TEXT PK, response_json TEXT, created_at DATETIME)
 */
final class IdempotencyService
{
    private const TTL_SECONDS = 86400;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Look up a cached response for the given idempotency key.
     *
     * @return array<string, mixed>|null the cached body, or null if not found / expired
     */
    public function lookup(string $key): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT response_json, created_at FROM idempotency_key WHERE `key` = :key LIMIT 1',
        );
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $createdAt = new \DateTimeImmutable((string) $row['created_at'], new \DateTimeZone('UTC'));
        $age = time() - $createdAt->getTimestamp();
        if ($age > self::TTL_SECONDS) {
            $this->pdo->prepare('DELETE FROM idempotency_key WHERE `key` = :key')
                ->execute(['key' => $key]);

            return null;
        }

        $decoded = json_decode((string) $row['response_json'], associative: true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Store a successful response for future replay.
     *
     * @param array<string, mixed> $body
     */
    public function store(string $key, array $body): void
    {
        $json = (string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now  = gmdate('Y-m-d H:i:s');

        $this->pdo->prepare(
            'INSERT INTO idempotency_key (`key`, response_json, created_at) '.
            'VALUES (:key, :json, :now) '.
            'ON DUPLICATE KEY UPDATE response_json = :json, created_at = :now',
        )->execute(['key' => $key, 'json' => $json, 'now' => $now]);
    }
}
