<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

use DateTimeImmutable;

/**
 * Immutable data shape of a single `auth_provider` row.
 *
 * Concrete {@see AuthProvider} implementations are constructed from
 * one of these (M4-D2): an `OidcProvider` reads `issuerOrMetadataUrl`
 * and `clientId` + decrypted `clientSecret`; a `SamlProvider` reads
 * `issuerOrMetadataUrl` + decrypted private key; a `LocalProvider`
 * uses only `id` and `name`.
 *
 * `clientSecret` is the **plaintext** — repository implementations
 * decrypt with `Saso\Infrastructure\Auth\Crypto\SecretEncryptor`
 * before constructing the record. Persisting changes goes back through
 * the repository, which encrypts on write. The plaintext does not
 * leave the {@see Repository\AuthProviderRepository} boundary unless
 * an `AuthProvider` implementation needs it.
 */
final readonly class AuthProviderRecord
{
    /**
     * @param array<string, mixed>|null $claimMapping
     */
    public function __construct(
        public AuthProviderId $id,
        public string $name,
        public AuthProviderType $type,
        public ?string $issuerOrMetadataUrl,
        public ?string $clientId,
        public ?string $clientSecret,
        public ?string $scopes,
        public ?array $claimMapping,
        public bool $enabled,
        public bool $isDefault,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public function withEnabled(bool $enabled): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            type: $this->type,
            issuerOrMetadataUrl: $this->issuerOrMetadataUrl,
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            scopes: $this->scopes,
            claimMapping: $this->claimMapping,
            enabled: $enabled,
            isDefault: $this->isDefault,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
