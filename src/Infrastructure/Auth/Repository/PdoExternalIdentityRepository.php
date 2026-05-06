<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Repository;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\ExternalIdentity;
use Saso\Domain\Auth\Repository\ExternalIdentityRepository;

/**
 * PDO-backed {@see ExternalIdentityRepository}.
 *
 * SQL is portable across MariaDB and SQLite. The composite primary
 * key `(auth_provider_id, external_subject)` plus the unique index on
 * `(member_id, auth_provider_id)` are enforced by both adapters.
 */
final class PdoExternalIdentityRepository implements ExternalIdentityRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function find(AuthProviderId $providerId, string $externalSubject): ?ExternalIdentity
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM member_external_identity WHERE auth_provider_id = :pid AND external_subject = :sub',
        );
        $stmt->execute([
            'pid' => $providerId->value,
            'sub' => $externalSubject,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listForMember(string $memberId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM member_external_identity WHERE member_id = :mid ORDER BY auth_provider_id ASC',
        );
        $stmt->execute(['mid' => $memberId]);

        return array_map(
            fn (array $row): ExternalIdentity => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function link(ExternalIdentity $identity): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO member_external_identity (member_id, auth_provider_id, external_subject, '.
            'created_at, updated_at, last_login_at) VALUES (:mid, :pid, :sub, :ca, :ua, :ll)',
        );
        $stmt->execute([
            'mid' => $identity->memberId,
            'pid' => $identity->authProviderId->value,
            'sub' => $identity->externalSubject,
            'ca'  => $identity->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'),
            'ua'  => $identity->updatedAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'),
            'll'  => $identity->lastLoginAt?->setTimezone($this->timezone)->format('Y-m-d H:i:s'),
        ]);
    }

    public function recordLogin(AuthProviderId $providerId, string $externalSubject): void
    {
        $now  = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE member_external_identity SET last_login_at = :now, updated_at = :now '.
            'WHERE auth_provider_id = :pid AND external_subject = :sub',
        );
        $stmt->execute([
            'now' => $now,
            'pid' => $providerId->value,
            'sub' => $externalSubject,
        ]);
    }

    public function unlink(AuthProviderId $providerId, string $externalSubject): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM member_external_identity WHERE auth_provider_id = :pid AND external_subject = :sub',
        );
        $stmt->execute([
            'pid' => $providerId->value,
            'sub' => $externalSubject,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): ExternalIdentity
    {
        $lastLogin = $row['last_login_at'] ?? null;

        return new ExternalIdentity(
            memberId: (string) $row['member_id'],
            authProviderId: new AuthProviderId((int) $row['auth_provider_id']),
            externalSubject: (string) $row['external_subject'],
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
            lastLoginAt: is_string($lastLogin) && $lastLogin !== ''
                ? new DateTimeImmutable($lastLogin, $this->timezone)
                : null,
        );
    }
}
