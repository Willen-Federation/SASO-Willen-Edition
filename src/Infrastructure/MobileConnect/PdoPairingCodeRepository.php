<?php

declare(strict_types=1);

namespace Saso\Infrastructure\MobileConnect;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Domain\MobileConnect\Repository\PairingCodeRepository;

final class PdoPairingCodeRepository implements PairingCodeRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findByTokenHash(string $hash): ?PairingCode
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pairing_code WHERE token_hash = :hash');
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function nextId(): int
    {
        $stmt = $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM pairing_code');
        if ($stmt === false) {
            return 1;
        }

        return (int) $stmt->fetchColumn();
    }

    public function save(PairingCode $code): PairingCode
    {
        $existing = $this->findByTokenHash($code->tokenHash);

        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pairing_code (id, token_hash, label, used, expires_at, created_at) '.
                'VALUES (:id, :hash, :label, :used, :expires_at, :created_at)',
            );
            $stmt->bindValue('id', $code->id, PDO::PARAM_INT);
            $stmt->bindValue('hash', $code->tokenHash);
            $stmt->bindValue('label', $code->label);
            $stmt->bindValue('used', $code->used ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('expires_at', $code->expiresAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('created_at', $code->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE pairing_code SET label = :label, used = :used WHERE id = :id',
            );
            $stmt->bindValue('id', $code->id, PDO::PARAM_INT);
            $stmt->bindValue('label', $code->label);
            $stmt->bindValue('used', $code->used ? 1 : 0, PDO::PARAM_INT);
            $stmt->execute();
        }

        $saved = $this->findByTokenHash($code->tokenHash);
        if ($saved === null) {
            throw new \RuntimeException(sprintf(
                'PdoPairingCodeRepository::save lost row %d after write.',
                $code->id,
            ));
        }

        return $saved;
    }

    public function deleteExpired(): int
    {
        $now  = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('DELETE FROM pairing_code WHERE expires_at < :now');
        $stmt->execute(['now' => $now]);

        return $stmt->rowCount();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): PairingCode
    {
        return new PairingCode(
            id: (int) $row['id'],
            tokenHash: (string) $row['token_hash'],
            label: (string) $row['label'],
            used: (int) $row['used'] === 1,
            expiresAt: new DateTimeImmutable((string) $row['expires_at'], $this->timezone),
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
        );
    }
}
