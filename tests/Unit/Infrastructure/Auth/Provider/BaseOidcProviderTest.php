<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Provider;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\CallbackRequest;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;
use Saso\Domain\Auth\LoginContext;
use Saso\Domain\Auth\LogoutContext;
use Saso\Infrastructure\Auth\OidcClientBridge;
use Saso\Infrastructure\Auth\Provider\BaseOidcProvider;

/**
 * Exercises the shared OIDC flow logic in BaseOidcProvider via a
 * test-only subclass, using a fake {@see OidcClientBridge} to feed in
 * canned ID-token claims / userinfo without speaking real HTTP to an IdP.
 */
final class BaseOidcProviderTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testBeginLoginRejectsCallbackOutsideAllowlist(): void
    {
        $provider = new FakeOidcProvider(
            $this->record(['_config' => [
                'redirect_uri_allowlist' => ['https://app.example.com/auth/callback'],
            ]]),
            'https://attacker.example.com/auth/callback',
            new BridgeFixture(),
        );

        $this->expectException(ProviderMisconfiguredException::class);
        $this->expectExceptionMessage('not in redirect_uri_allowlist');

        $provider->beginLogin(new LoginContext('/', 'state', 'nonce'));
    }

    public function testCompleteLoginRequiresSessionStateAndNonce(): void
    {
        $provider = $this->buildProvider(new BridgeFixture());

        $this->expectException(AuthFailedException::class);

        $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'whatever'],
        ));
    }

    public function testCompleteLoginRejectsTamperedState(): void
    {
        $provider = $this->buildProvider(new BridgeFixture());

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $this->expectException(AuthFailedException::class);

        try {
            $provider->completeLogin(new CallbackRequest(
                method: 'GET',
                uri: '/auth/callback',
                query: ['state' => 'tampered'],
            ));
        } finally {
            self::assertArrayNotHasKey('auth.state', $_SESSION);
            self::assertArrayNotHasKey('auth.nonce', $_SESSION);
        }
    }

    public function testCompleteLoginRejectsIdTokenWithoutNonceClaim(): void
    {
        // jumbojett's verifyJWTClaims() silently passes when the `nonce`
        // claim is absent — we re-assert presence to defend against a
        // non-compliant IdP. A token stripped of nonce must NOT authenticate.
        $bridge = new BridgeFixture();
        $bridge->fakeIdTokenPayload = ['sub' => 'user-1', 'email' => 'a@example.com'];

        $provider = $this->buildProvider($bridge);

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $this->expectException(AuthFailedException::class);
        $this->expectExceptionMessage('nonce missing or mismatched');

        $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'expected'],
        ));
    }

    public function testCompleteLoginRejectsIdTokenWithMismatchedNonce(): void
    {
        $bridge = new BridgeFixture();
        $bridge->fakeIdTokenPayload = ['sub' => 'user-1', 'nonce' => 'forged-nonce'];

        $provider = $this->buildProvider($bridge);

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $this->expectException(AuthFailedException::class);
        $this->expectExceptionMessage('nonce missing or mismatched');

        $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'expected'],
        ));
    }

    public function testCompleteLoginRejectsUserinfoSubMismatch(): void
    {
        // OIDC Core §5.3.2 — userinfo sub MUST equal id_token sub.
        $bridge = new BridgeFixture();
        $bridge->fakeIdTokenPayload = ['sub' => 'user-1', 'nonce' => 'expected-nonce'];
        $bridge->fakeUserInfo = ['sub' => 'attacker-takeover', 'email' => 'attacker@evil.example'];

        $provider = $this->buildProvider($bridge);

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $this->expectException(AuthFailedException::class);
        $this->expectExceptionMessage('userinfo `sub` does not match');

        $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'expected'],
        ));
    }

    public function testCompleteLoginAcceptsValidIdTokenWithMatchingUserinfo(): void
    {
        $bridge = new BridgeFixture();
        $bridge->fakeIdTokenPayload = [
            'sub'   => 'user-1',
            'nonce' => 'expected-nonce',
            'email' => 'id-token@example.com',
        ];
        $bridge->fakeUserInfo = [
            'sub'  => 'user-1',
            'name' => 'Alice Example',
        ];
        $bridge->fakeIdToken = 'opaque.id.token';

        $provider = $this->buildProvider($bridge);

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $identity = $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'expected'],
        ));

        self::assertSame('user-1', $identity->externalSubject);
        // ID-token email wins over userinfo silence; userinfo `name` fills in.
        self::assertSame('id-token@example.com', $identity->email);
        self::assertSame('Alice Example', $identity->displayName);
        // id_token stashed for RP-Initiated Logout
        self::assertSame('opaque.id.token', $_SESSION['auth.id_token']);
        // Pending state / nonce cleared after success.
        self::assertArrayNotHasKey('auth.state', $_SESSION);
        self::assertArrayNotHasKey('auth.nonce', $_SESSION);
    }

    public function testCompleteLoginIdTokenClaimsTakePrecedenceOverUserinfo(): void
    {
        // ID-token claims are signed; userinfo is best-effort. If both
        // carry the same key, the signed claim must win.
        $bridge = new BridgeFixture();
        $bridge->fakeIdTokenPayload = [
            'sub'   => 'user-1',
            'nonce' => 'expected-nonce',
            'email' => 'authoritative@example.com',
        ];
        $bridge->fakeUserInfo = [
            'sub'   => 'user-1',
            'email' => 'tampered-userinfo@evil.example',
        ];

        $provider = $this->buildProvider($bridge);

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $identity = $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'expected'],
        ));

        self::assertSame('authoritative@example.com', $identity->email);
    }

    public function testCompleteLoginToleratesUserinfoFetchFailure(): void
    {
        // The userinfo endpoint is optional. A 5xx / connection error there
        // must not abort the login as long as the ID-token claims suffice.
        $bridge = new BridgeFixture();
        $bridge->fakeIdTokenPayload = [
            'sub'   => 'user-1',
            'nonce' => 'expected-nonce',
            'email' => 'a@example.com',
        ];
        $bridge->userInfoThrows = new \RuntimeException('upstream 503');

        $provider = $this->buildProvider($bridge);

        $_SESSION['auth.state'] = 'expected';
        $_SESSION['auth.nonce'] = 'expected-nonce';

        $identity = $provider->completeLogin(new CallbackRequest(
            method: 'GET',
            uri: '/auth/callback',
            query: ['state' => 'expected'],
        ));

        self::assertSame('user-1', $identity->externalSubject);
        self::assertSame('a@example.com', $identity->email);
    }

    public function testBeginLogoutReturnsNullWhenIdpHasNoEndSessionEndpoint(): void
    {
        $bridge = new BridgeFixture();
        $bridge->endSessionEndpoint = '';

        $provider = $this->buildProvider($bridge);

        self::assertNull($provider->beginLogout(new LogoutContext('https://example.com/after')));
    }

    public function testBeginLogoutIncludesIdTokenHintAndPostLogoutRedirectUri(): void
    {
        $bridge = new BridgeFixture();
        $bridge->endSessionEndpoint = 'https://idp.example.com/logout';

        $provider = $this->buildProvider($bridge);

        $redirect = $provider->beginLogout(new LogoutContext(
            returnTo: 'https://app.example.com/bye',
            idTokenHint: 'header.payload.sig',
        ));

        self::assertNotNull($redirect);
        self::assertStringContainsString(
            'post_logout_redirect_uri=https%3A%2F%2Fapp.example.com%2Fbye',
            $redirect->url,
        );
        self::assertStringContainsString('id_token_hint=header.payload.sig', $redirect->url);
        self::assertStringContainsString('client_id=cid', $redirect->url);
    }

    private function buildProvider(BridgeFixture $bridge): FakeOidcProvider
    {
        return new FakeOidcProvider(
            $this->record(null),
            'http://localhost/auth/callback',
            $bridge,
        );
    }

    /**
     * @param array<string, mixed>|null $claimMapping
     */
    private function record(?array $claimMapping): AuthProviderRecord
    {
        return new AuthProviderRecord(
            id: new AuthProviderId(1),
            name: 'Test Generic',
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: 'https://idp.example.com',
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

/**
 * In-memory bridge that returns canned values instead of speaking real OIDC.
 */
final class BridgeFixture extends OidcClientBridge
{
    /** @var array<string, mixed> */
    public array $fakeIdTokenPayload = [];

    /** @var array<string, mixed> */
    public array $fakeUserInfo = [];

    public ?\Throwable $userInfoThrows = null;

    public string $fakeIdToken = '';

    public string $endSessionEndpoint = '';

    public function __construct()
    {
        // Bypass the jumbojett parent constructor — it would otherwise wire
        // up real HTTP probes for discovery / JWKS. The completeLogin path
        // only calls the public methods we override here.
    }

    public function getEndpointConfig(string $param, mixed $default = null): mixed
    {
        if ($param === 'end_session_endpoint') {
            return $this->endSessionEndpoint !== '' ? $this->endSessionEndpoint : $default;
        }
        return $default;
    }

    public function setRedirectURL(string $url): void
    {
    }

    /** @param array<int, string> $scope */
    public function addScope($scope): void
    {
    }

    public function setNonceValue(string $nonce): string
    {
        return $nonce;
    }

    public function authenticate(): bool
    {
        return true;
    }

    public function getIdTokenPayload(): \stdClass
    {
        $obj = new \stdClass();
        foreach ($this->fakeIdTokenPayload as $k => $v) {
            $obj->{$k} = $v;
        }
        return $obj;
    }

    public function requestUserInfo(?string $attribute = null): mixed
    {
        if ($this->userInfoThrows !== null) {
            throw $this->userInfoThrows;
        }
        $obj = new \stdClass();
        foreach ($this->fakeUserInfo as $k => $v) {
            $obj->{$k} = $v;
        }
        return $obj;
    }

    public function getIdToken(): mixed
    {
        return $this->fakeIdToken;
    }
}

/**
 * Test-only subclass that returns the supplied bridge instead of constructing
 * a real {@see OidcClientBridge} (which would hit the IdP discovery URL).
 */
final class FakeOidcProvider extends BaseOidcProvider
{
    public function __construct(
        AuthProviderRecord $record,
        string $callbackUrl,
        private readonly BridgeFixture $bridge,
    ) {
        parent::__construct($record, $callbackUrl);
    }

    protected function buildClient(): OidcClientBridge
    {
        return $this->bridge;
    }
}
