<?php

declare(strict_types=1);

namespace Saso\Infrastructure\MobileConnect;

use DateTimeZone;
use PDO;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;

final class PdoDeviceTokenRepository implements DeviceTokenRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findByTokenHash(string $hash): ?DeviceToken
    {
        $stmt = $this->pdo->prepare('SELECT * FROM device_token WHERE token_hash = :hash');
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findById(int $id): ?DeviceToken
    {
        $stmt = $this->pdo->prepare('SELECT * FROM device_token WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM device_token ORDER BY created_at DESC');
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): DeviceToken => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function nextId(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM device_token');
        if ($stmt === false) {
            return 1;
        }

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): DeviceToken
    {
        $lastUsed = $row['last_used_at'] ?? null;

        return new DeviceToken(
            id: (int) $row['id'],
            tokenHash: (string) $row['token_hash'],
            deviceName: (string) $row['device_name'],
            revoked: (int) $row['revoked'] === 1,
            lastUsedAt: is_string($lastUsed) && $lastUsed !== ''
                ? new \DateTimeImmutable($lastUsed, $this->timezone)
                : null,
            expiresAt: new \DateTimeImmutable((string) $row['expires_at'], $this->timezone),
            createdAt: new \DateTimeImmutable((string) $row['created_at'], $this->timezone),
        );
    }

    public function save(DeviceToken $token): DeviceToken
    {
        $existing = $this->findById($token->id);

        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO device_token (id, token_hash, device_name, revoked, last_used_at, expires_at, created_at) '.
                'VALUES (:id, :hash, :device_name, :revoked, :last_used_at, :expires_at, :created_at)',
            );
            $stmt->bindValue('id', $token->id, PDO::PARAM_INT);
            $stmt->bindValue('hash', $token->tokenHash);
            $stmt->bindValue('device_name', $token->deviceName);
            $stmt->bindValue('revoked', $token->revoked ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('last_used_at', $token->lastUsedAt?->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('expires_at', $token->expiresAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('created_at', $token->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE device_token SET device_name = :device_name, revoked = :revoked, '.
                'last_used_at = :last_used_at WHERE id = :id',
            );
            $stmt->bindValue('id', $token->id, PDO::PARAM_INT);
            $stmt->bindValue('device_name', $token->deviceName);
            $stmt->bindValue('revoked', $token->revoked ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('last_used_at', $token->lastUsedAt?->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->execute();
        }

        $saved = $this->findById($token->id);
        if ($saved === null) {
            throw new \RuntimeException(sprintf(
                'PdoDeviceTokenRepository::save lost row %d after write.',
                $token->id,
            ));
        }

        return $saved;
    }
}
