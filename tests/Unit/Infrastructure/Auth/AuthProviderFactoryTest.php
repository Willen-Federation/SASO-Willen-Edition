<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Infrastructure\Auth\AuthProviderFactory;
use Saso\Infrastructure\Auth\Provider\Auth0Provider;
use Saso\Infrastructure\Auth\Provider\GenericOidcProvider;

final class AuthProviderFactoryTest extends TestCase
{
    public function testExplicitAuth0FlavorBuildsAuth0Provider(): void
    {
        $factory = $this->factory();
        $record  = $this->record(
            issuer: 'https://tenant.auth0.com',
            claimMapping: ['_config' => ['flavor' => 'auth0', 'domain' => 'tenant.auth0.com']],
        );

        self::assertInstanceOf(Auth0Provider::class, $factory->forRecord($record));
    }

    public function testRescueHeuristicDetectsAuth0FromIssuerHost(): void
    {
        // Pre-fix wizard rows had claim_mapping = NULL. Without the rescue
        // the factory falls back to GenericOidcProvider; with it, any
        // *.auth0.com host is correctly routed to Auth0Provider so existing
        // broken rows recover without needing a re-save.
        $factory = $this->factory();
        $record  = $this->record(
            issuer: 'https://tenant.auth0.com',
            claimMapping: null,
        );

        self::assertInstanceOf(Auth0Provider::class, $factory->forRecord($record));
    }

    public function testRescueHeuristicAcceptsNestedAuth0Subdomain(): void
    {
        $factory = $this->factory();
        $record  = $this->record(
            issuer: 'https://acme.eu.auth0.com/',
            claimMapping: null,
        );

        self::assertInstanceOf(Auth0Provider::class, $factory->forRecord($record));
    }

    public function testNonAuth0HostStaysGeneric(): void
    {
        $factory = $this->factory();
        $record  = $this->record(
            issuer: 'https://sso.example.com/realms/foo',
            claimMapping: null,
        );

        $provider = $factory->forRecord($record);
        self::assertInstanceOf(GenericOidcProvider::class, $provider);
        self::assertNotInstanceOf(Auth0Provider::class, $provider);
    }

    public function testHostnameSpoofingDoesNotTriggerHeuristic(): void
    {
        // "auth0.com" has to be the host (or a real subdomain). A path
        // segment or a host suffix that doesn't end in `.auth0.com` must
        // not flip the flavor.
        $factory = $this->factory();
        $record  = $this->record(
            issuer: 'https://attacker-auth0.com.evil.example/realms/foo',
            claimMapping: null,
        );

        self::assertNotInstanceOf(Auth0Provider::class, $factory->forRecord($record));
    }

    private function factory(): AuthProviderFactory
    {
        return new AuthProviderFactory(
            new class () implements AuthProviderRepository {
                public function findById(AuthProviderId $id): ?AuthProviderRecord
                {
                    return null;
                }
                public function listAll(): array
                {
                    return [];
                }
                public function listEnabled(): array
                {
                    return [];
                }
                public function save(AuthProviderRecord $record): AuthProviderRecord
                {
                    return $record;
                }
                public function delete(AuthProviderId $id): void
                {
                }
            },
            new PDO('sqlite::memory:'),
            'http://localhost:8080',
        );
    }

    /**
     * @param array<string, mixed>|null $claimMapping
     */
    private function record(string $issuer, ?array $claimMapping): AuthProviderRecord
    {
        $now = new DateTimeImmutable('2026-05-03 00:00:00');
        return new AuthProviderRecord(
            id: new AuthProviderId(1),
            name: 'test-provider',
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: $issuer,
            clientId: 'cid',
            clientSecret: 'sec',
            scopes: 'openid profile email',
            claimMapping: $claimMapping,
            enabled: true,
            isDefault: false,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
