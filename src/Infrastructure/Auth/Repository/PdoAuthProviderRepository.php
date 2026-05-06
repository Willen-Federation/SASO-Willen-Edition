<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Repository;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

/**
 * PDO-backed {@see AuthProviderRepository}.
 *
 * Reads decrypt `client_secret_encrypted` via {@see SecretEncryptor}
 * before constructing a record; writes encrypt on the way back. The
 * plaintext does not appear in any log line — the repository is
 * deliberately quiet.
 *
 * SQL is portable enough to run on both MariaDB / MySQL (production)
 * and SQLite (tests). The `enabled` and `is_default` columns are
 * persisted as integer 0/1 because SQLite does not have a native
 * BOOLEAN type; MariaDB transparently accepts the same.
 *
 * `claim_mapping` is JSON-encoded into a TEXT column on SQLite and
 * the native JSON type on MariaDB. Both round-trip through
 * `json_encode` / `json_decode` with the same semantics for the
 * shapes we use here (string-keyed maps of strings).
 */
final class PdoAuthProviderRepository implements AuthProviderRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SecretEncryptor $encryptor,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findById(AuthProviderId $id): ?AuthProviderRecord
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM auth_provider WHERE id = :id',
        );
        $stmt->execute(['id' => $id->value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query($this->orderedSelect());
        if ($stmt === false) {
            return [];
        }

        return $this->hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listEnabled(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM auth_provider WHERE enabled = 1 ORDER BY is_default DESC, name ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return $this->hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function save(AuthProviderRecord $record): AuthProviderRecord
    {
        $now    = new DateTimeImmutable('now', $this->timezone);
        $secret = $record->clientSecret;
        $cipher = $secret === null ? null : $this->encryptor->encrypt($secret);

        $existing = $this->findById($record->id);
        if ($existing === null) {
            $insert = $this->pdo->prepare(
                'INSERT INTO auth_provider (id, name, type, issuer_or_metadata_url, client_id, '.
                'client_secret_encrypted, scopes, claim_mapping, enabled, is_default, '.
                'created_at, updated_at) VALUES (:id, :name, :type, :url, :client_id, '.
                ':secret, :scopes, :claim_mapping, :enabled, :is_default, :created_at, :updated_at)',
            );
            $insert->bindValue('id', $record->id->value, PDO::PARAM_INT);
            $insert->bindValue('name', $record->name);
            $insert->bindValue('type', $record->type->value);
            $insert->bindValue('url', $record->issuerOrMetadataUrl);
            $insert->bindValue('client_id', $record->clientId);
            $insert->bindValue('secret', $cipher, $cipher === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
            $insert->bindValue('scopes', $record->scopes);
            $insert->bindValue(
                'claim_mapping',
                $record->claimMapping === null ? null : self::encodeJson($record->claimMapping),
            );
            $insert->bindValue('enabled', $record->enabled ? 1 : 0, PDO::PARAM_INT);
            $insert->bindValue('is_default', $record->isDefault ? 1 : 0, PDO::PARAM_INT);
            $insert->bindValue('created_at', $record->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $insert->bindValue('updated_at', $now->format('Y-m-d H:i:s'));
            $insert->execute();

            if ($record->id->value === 0) {
                $newId = (int) $this->pdo->lastInsertId();
                return $this->findById(new AuthProviderId($newId))
                    ?? throw new \RuntimeException('PdoAuthProviderRepository::save lost row after write.');
            }
        } else {
            $update = $this->pdo->prepare(
                'UPDATE auth_provider SET name = :name, type = :type, '.
                'issuer_or_metadata_url = :url, client_id = :client_id, '.
                'client_secret_encrypted = :secret, scopes = :scopes, '.
                'claim_mapping = :claim_mapping, enabled = :enabled, '.
                'is_default = :is_default, updated_at = :updated_at WHERE id = :id',
            );
            $update->bindValue('id', $record->id->value, PDO::PARAM_INT);
            $update->bindValue('name', $record->name);
            $update->bindValue('type', $record->type->value);
            $update->bindValue('url', $record->issuerOrMetadataUrl);
            $update->bindValue('client_id', $record->clientId);
            $update->bindValue('secret', $cipher, $cipher === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
            $update->bindValue('scopes', $record->scopes);
            $update->bindValue(
                'claim_mapping',
                $record->claimMapping === null ? null : self::encodeJson($record->claimMapping),
            );
            $update->bindValue('enabled', $record->enabled ? 1 : 0, PDO::PARAM_INT);
            $update->bindValue('is_default', $record->isDefault ? 1 : 0, PDO::PARAM_INT);
            $update->bindValue('updated_at', $now->format('Y-m-d H:i:s'));
            $update->execute();
        }

        $reread = $this->findById($record->id);
        if ($reread === null) {
            throw new \RuntimeException(sprintf(
                'PdoAuthProviderRepository::save lost row %d after write.',
                $record->id->value,
            ));
        }

        return $reread;
    }

    public function delete(AuthProviderId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM auth_provider WHERE id = :id');
        $stmt->execute(['id' => $id->value]);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<AuthProviderRecord>
     */
    private function hydrateAll(array $rows): array
    {
        return array_map(fn (array $row): AuthProviderRecord => $this->hydrate($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): AuthProviderRecord
    {
        $cipher = $row['client_secret_encrypted'] ?? null;
        $secret = is_string($cipher) && $cipher !== ''
            ? $this->encryptor->decrypt($cipher)
            : null;

        $claimMapping = null;
        if (isset($row['claim_mapping']) && is_string($row['claim_mapping']) && $row['claim_mapping'] !== '') {
            /** @var array<string, string>|null $decoded */
            $decoded      = json_decode($row['claim_mapping'], associative: true);
            $claimMapping = is_array($decoded) ? $decoded : null;
        }

        return new AuthProviderRecord(
            id: new AuthProviderId((int) $row['id']),
            name: (string) $row['name'],
            type: AuthProviderType::from((string) $row['type']),
            issuerOrMetadataUrl: isset($row['issuer_or_metadata_url']) && is_string($row['issuer_or_metadata_url'])
                ? $row['issuer_or_metadata_url']
                : null,
            clientId: isset($row['client_id']) && is_string($row['client_id']) ? $row['client_id'] : null,
            clientSecret: $secret,
            scopes: isset($row['scopes']) && is_string($row['scopes']) ? $row['scopes'] : null,
            claimMapping: $claimMapping,
            enabled: (int) $row['enabled'] === 1,
            isDefault: (int) $row['is_default'] === 1,
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }

    private function orderedSelect(): string
    {
        return 'SELECT * FROM auth_provider ORDER BY is_default DESC, name ASC';
    }

    /**
     * @param array<string, string> $payload
     */
    private static function encodeJson(array $payload): string
    {
        return (string) json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
