<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Provider;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;
use Saso\Domain\Auth\LoginContext;
use Saso\Infrastructure\Auth\Provider\Auth0Provider;

final class Auth0ProviderTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testConstructorRequiresIssuerOrDomain(): void
    {
        $this->expectException(ProviderMisconfiguredException::class);
        $this->expectExceptionMessage('_config.domain or a valid issuer_or_metadata_url');

        new Auth0Provider(
            $this->record(claimMapping: ['_config' => []], issuer: ''),
            'http://localhost/auth/callback',
        );
    }

    public function testConstructorAcceptsExplicitDomain(): void
    {
        $provider = new Auth0Provider(
            $this->record(
                claimMapping: ['_config' => ['domain' => 'tenant.eu.auth0.com']],
                issuer: '',
            ),
            'http://localhost/auth/callback',
        );

        self::assertSame(AuthProviderType::Oidc, $provider->type());
        self::assertSame('Test Auth0', $provider->displayName());
        self::assertTrue($provider->supportsLogout());
    }

    public function testConstructorDerivesDomainFromIssuerHost(): void
    {
        // No exception — host from issuer is enough.
        $provider = new Auth0Provider(
            $this->record(
                claimMapping: null,
                issuer: 'https://tenant.auth0.com/',
            ),
            'http://localhost/auth/callback',
        );

        self::assertSame(1, $provider->id()->value);
    }

    public function testConstructorRejectsMissingClientId(): void
    {
        $this->expectException(ProviderMisconfiguredException::class);
        $this->expectExceptionMessage('client_id');

        new Auth0Provider(
            new AuthProviderRecord(
                id: new AuthProviderId(1),
                name: 'Test Auth0',
                type: AuthProviderType::Oidc,
                issuerOrMetadataUrl: 'https://tenant.auth0.com',
                clientId: '',
                clientSecret: 'sec',
                scopes: null,
                claimMapping: null,
                enabled: true,
                isDefault: false,
                createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ),
            'http://localhost/auth/callback',
        );
    }

    public function testConstructorRejectsMissingClientSecret(): void
    {
        $this->expectException(ProviderMisconfiguredException::class);
        $this->expectExceptionMessage('client_secret');

        new Auth0Provider(
            new AuthProviderRecord(
                id: new AuthProviderId(1),
                name: 'Test Auth0',
                type: AuthProviderType::Oidc,
                issuerOrMetadataUrl: 'https://tenant.auth0.com',
                clientId: 'cid',
                clientSecret: '',
                scopes: null,
                claimMapping: null,
                enabled: true,
                isDefault: false,
                createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ),
            'http://localhost/auth/callback',
        );
    }

    public function testBeginLoginRejectsCallbackOutsideAllowlist(): void
    {
        $provider = new Auth0Provider(
            $this->record(
                claimMapping: ['_config' => [
                    'domain' => 'tenant.auth0.com',
                    'redirect_uri_allowlist' => ['https://app.example.com/auth/callback'],
                ]],
            ),
            'https://attacker.example.com/auth/callback',
        );

        $this->expectException(ProviderMisconfiguredException::class);
        $this->expectExceptionMessage('not in redirect_uri_allowlist');

        $provider->beginLogin(new LoginContext('/', 'state', 'nonce'));
    }

    public function testBeginLoginAcceptsCallbackInAllowlist(): void
    {
        $provider = new Auth0Provider(
            $this->record(
                claimMapping: ['_config' => [
                    'domain' => 'tenant.auth0.com',
                    'redirect_uri_allowlist' => ['http://localhost/auth/callback'],
                ]],
            ),
            'http://localhost/auth/callback',
        );

        $redirect = $provider->beginLogin(new LoginContext('/return-to', 'state-value', 'nonce-value'));

        self::assertSame(302, $redirect->status);
        self::assertStringStartsWith('https://tenant.auth0.com/', $redirect->url);
        // The state we passed should appear in the IdP redirect URL.
        self::assertStringContainsString('state=state-value', $redirect->url);
        // Our session keys mirror the values so the orchestrator can read them.
        self::assertSame('state-value', $_SESSION['auth.state']);
        self::assertSame(1, $_SESSION['auth.provider_id']);
        self::assertSame('/return-to', $_SESSION['auth.return_to']);
    }

    public function testBeginLoginWithEmptyAllowlistDoesNotEnforce(): void
    {
        // Empty / missing allowlist = unconstrained (matches BaseOidcProvider).
        $provider = new Auth0Provider(
            $this->record(claimMapping: ['_config' => ['domain' => 'tenant.auth0.com']]),
            'http://localhost/auth/callback',
        );

        $redirect = $provider->beginLogin(new LoginContext('/', 'state', 'nonce'));

        self::assertSame(302, $redirect->status);
    }

    public function testCompleteLoginRequiresPendingState(): void
    {
        $provider = new Auth0Provider(
            $this->record(claimMapping: ['_config' => ['domain' => 'tenant.auth0.com']]),
            'http://localhost/auth/callback',
        );

        // No prior beginLogin → no auth.state in session → must reject early.
        $this->expectException(AuthFailedException::class);

        $provider->completeLogin(new \Saso\Domain\Auth\CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'whatever', 'code' => 'abc'],
        ));
    }

    public function testCompleteLoginRejectsTamperedState(): void
    {
        $provider = new Auth0Provider(
            $this->record(claimMapping: ['_config' => ['domain' => 'tenant.auth0.com']]),
            'http://localhost/auth/callback',
        );

        $_SESSION['auth.state'] = 'expected-state-12345';

        $this->expectException(AuthFailedException::class);

        try {
            $provider->completeLogin(new \Saso\Domain\Auth\CallbackRequest(
                method: 'GET',
                uri: '/auth/callback',
                query: ['state' => 'tampered', 'code' => 'abc'],
            ));
        } finally {
            // The mismatched state must be cleared so a retry does not see
            // a half-stale value (defensive — prevents replay loops).
            self::assertArrayNotHasKey('auth.state', $_SESSION);
        }
    }

    /**
     * @param array<string, mixed>|null $claimMapping
     */
    private function record(
        ?array $claimMapping,
        string $issuer = 'https://tenant.auth0.com',
    ): AuthProviderRecord {
        return new AuthProviderRecord(
            id: new AuthProviderId(1),
            name: 'Test Auth0',
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: $issuer === '' ? null : $issuer,
            clientId: 'cid',
            clientSecret: 'sec',
            scopes: 'openid profile email',
            claimMapping: $claimMapping,
            enabled: true,
            isDefault: false,
            createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }
}
