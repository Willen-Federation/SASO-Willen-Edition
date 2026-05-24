<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Mobile;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Auth\Exception\AuthRequiredException;
use Saso\Domain\MobileConnect\Exception\ScopeInsufficientException;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\HttpRequest;

#[CoversClass(JwtGuard::class)]
final class JwtGuardTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    public function testAuthenticateReturnsClaimsForValidBearerToken(): void
    {
        $jwt   = new JwtService(self::SECRET);
        // Mint at "now" — the guard verifies against the system clock, so
        // anchoring the issue time to a fixed past date would make the test
        // time-bomb the moment the access-token TTL elapsed in CI.
        $now   = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token = $jwt->issue(42, $now, 'admin_test', ['items:read'])['token'];

        $guard = new JwtGuard($jwt);
        $claims = $guard->authenticate($this->makeRequest($token));

        self::assertSame(42, $claims->deviceId);
        self::assertSame('admin_test', $claims->memberId);
        self::assertSame(['items:read'], $claims->scopes);
    }

    public function testAuthenticateRejectsRequestWithoutBearerHeader(): void
    {
        $guard = new JwtGuard(new JwtService(self::SECRET));

        try {
            $guard->authenticate(new HttpRequest(method: 'GET', path: '/api/v1/items'));
            self::fail('Expected AuthRequiredException');
        } catch (AuthRequiredException $e) {
            // Must be SASO-AUTH-1004 (HTTP 401) — not SASO-INFRA-9000 (500).
            // ProblemExceptionHandler picks the response code off this enum.
            self::assertSame(ErrorCode::AuthUnauthorized, $e->errorCode());
            self::assertStringContainsString('Authorization header', $e->getMessage());
        }
    }

    public function testAuthenticateRejectsMalformedAuthorizationHeader(): void
    {
        $guard = new JwtGuard(new JwtService(self::SECRET));

        try {
            $guard->authenticate(new HttpRequest(
                method: 'GET',
                path: '/api/v1/items',
                headers: ['authorization' => 'Basic dXNlcjpwYXNz'],
            ));
            self::fail('Expected AuthRequiredException');
        } catch (AuthRequiredException $e) {
            self::assertSame(ErrorCode::AuthUnauthorized, $e->errorCode());
        }
    }

    public function testAuthenticateRejectsTamperedTokenAsAuthRequired(): void
    {
        $jwt   = new JwtService(self::SECRET);
        // Mint at "now" — the guard verifies against the system clock, so
        // anchoring the issue time to a fixed past date would make the test
        // time-bomb the moment the access-token TTL elapsed in CI.
        $now   = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token = $jwt->issue(7, $now, 'admin_test', ['items:read'])['token'];

        // Tamper the signature — without conversion this would surface as
        // SASO-INFRA-9000 (500) because JwtService throws plain RuntimeException.
        // The guard must re-wrap as AuthRequiredException so the response is 401.
        $tampered = (string) preg_replace('/[A-Za-z0-9_-]+$/', 'aaaa', $token);

        $guard = new JwtGuard($jwt);

        try {
            $guard->authenticate($this->makeRequest($tampered));
            self::fail('Expected AuthRequiredException');
        } catch (AuthRequiredException $e) {
            self::assertSame(ErrorCode::AuthUnauthorized, $e->errorCode());
            // Original RuntimeException must survive as $previous so operators
            // can still see whether it was "invalid signature" vs "expired" etc.
            self::assertNotNull($e->getPrevious());
        }
    }

    public function testRequireScopeReturnsClaimsWhenScopeIsPresent(): void
    {
        $jwt   = new JwtService(self::SECRET);
        // Mint at "now" — the guard verifies against the system clock, so
        // anchoring the issue time to a fixed past date would make the test
        // time-bomb the moment the access-token TTL elapsed in CI.
        $now   = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token = $jwt->issue(7, $now, 'admin_test', ['items:read', 'items:write'])['token'];

        $guard = new JwtGuard($jwt);
        $claims = $guard->requireScope($this->makeRequest($token), 'items:write');

        self::assertSame(7, $claims->deviceId);
        self::assertTrue($claims->hasScope('items:write'));
    }

    public function testRequireScopeRejectsTokenWithoutScope(): void
    {
        $jwt   = new JwtService(self::SECRET);
        // Mint at "now" — the guard verifies against the system clock, so
        // anchoring the issue time to a fixed past date would make the test
        // time-bomb the moment the access-token TTL elapsed in CI.
        $now   = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token = $jwt->issue(7, $now, 'admin_test', ['items:read'])['token'];

        $guard = new JwtGuard($jwt);

        try {
            $guard->requireScope($this->makeRequest($token), 'items:write');
            self::fail('Expected ScopeInsufficientException');
        } catch (ScopeInsufficientException $e) {
            self::assertSame(ErrorCode::MobileScopeInsufficient, $e->errorCode());
            // The required scope must be in the exception context — operators
            // grep logs by `requiredScope` to find tokens issued with too narrow
            // a scope set, so the breadcrumb must survive.
            self::assertSame(['requiredScope' => 'items:write'], $e->context());
            self::assertStringContainsString('items:write', $e->getMessage());
        }
    }

    public function testRequireScopeRejectsTokenWithEmptyScopeList(): void
    {
        $jwt   = new JwtService(self::SECRET);
        // Mint at "now" — the guard verifies against the system clock, so
        // anchoring the issue time to a fixed past date would make the test
        // time-bomb the moment the access-token TTL elapsed in CI.
        $now   = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        // Legacy token: minted before scopes existed → no scp claim → empty list.
        $token = $jwt->issue(7, $now)['token'];

        $guard = new JwtGuard($jwt);

        $this->expectException(ScopeInsufficientException::class);
        $guard->requireScope($this->makeRequest($token), 'items:read');
    }

    public function testRequireScopePropagatesAuthFailureWhenHeaderMissing(): void
    {
        $guard = new JwtGuard(new JwtService(self::SECRET));

        // Bare-bones request with no Authorization header — authenticate() must
        // fail first (401) so we never reach the scope check (403). The order
        // matters: an unauthenticated caller probing for scope info would
        // otherwise see different responses for "no token" vs "wrong scope".
        $this->expectException(AuthRequiredException::class);
        $this->expectExceptionMessage('Authorization header');
        $guard->requireScope(new HttpRequest(method: 'GET', path: '/api/v1/items'), 'items:read');
    }

    public function testRequireScopeRejectsTamperedToken(): void
    {
        $jwt   = new JwtService(self::SECRET);
        // Mint at "now" — the guard verifies against the system clock, so
        // anchoring the issue time to a fixed past date would make the test
        // time-bomb the moment the access-token TTL elapsed in CI.
        $now   = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token = $jwt->issue(7, $now, 'admin_test', ['items:read'])['token'];

        // Tamper the signature — verify() throws RuntimeException; the guard
        // re-wraps it as AuthRequiredException (→ 401) so the scope check is
        // never reached and the response stays consistent for unauthenticated
        // callers.
        $tampered = (string) preg_replace('/[A-Za-z0-9_-]+$/', 'aaaa', $token);

        $guard = new JwtGuard($jwt);

        $this->expectException(AuthRequiredException::class);
        $guard->requireScope($this->makeRequest($tampered), 'items:read');
    }

    private function makeRequest(string $token): HttpRequest
    {
        return new HttpRequest(
            method: 'GET',
            path: '/api/v1/items',
            headers: ['authorization' => 'Bearer '.$token],
        );
    }
}
