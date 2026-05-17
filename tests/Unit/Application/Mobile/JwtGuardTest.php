<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Mobile;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Application\Mobile\JwtGuard;
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
        $now   = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Authorization header');
        $guard->authenticate(new HttpRequest(method: 'GET', path: '/api/v1/items'));
    }

    public function testAuthenticateRejectsMalformedAuthorizationHeader(): void
    {
        $guard = new JwtGuard(new JwtService(self::SECRET));

        $this->expectException(RuntimeException::class);
        $guard->authenticate(new HttpRequest(
            method: 'GET',
            path: '/api/v1/items',
            headers: ['authorization' => 'Basic dXNlcjpwYXNz'],
        ));
    }

    public function testRequireScopeReturnsClaimsWhenScopeIsPresent(): void
    {
        $jwt   = new JwtService(self::SECRET);
        $now   = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
        $token = $jwt->issue(7, $now, 'admin_test', ['items:read', 'items:write'])['token'];

        $guard = new JwtGuard($jwt);
        $claims = $guard->requireScope($this->makeRequest($token), 'items:write');

        self::assertSame(7, $claims->deviceId);
        self::assertTrue($claims->hasScope('items:write'));
    }

    public function testRequireScopeRejectsTokenWithoutScope(): void
    {
        $jwt   = new JwtService(self::SECRET);
        $now   = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
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
        $now   = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Authorization header');
        $guard->requireScope(new HttpRequest(method: 'GET', path: '/api/v1/items'), 'items:read');
    }

    public function testRequireScopeRejectsTamperedToken(): void
    {
        $jwt   = new JwtService(self::SECRET);
        $now   = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
        $token = $jwt->issue(7, $now, 'admin_test', ['items:read'])['token'];

        // Tamper the signature — verify() must throw RuntimeException before
        // scope evaluation runs, so the guard never leaks scope info to an
        // unauthenticated caller.
        $tampered = (string) preg_replace('/[A-Za-z0-9_-]+$/', 'aaaa', $token);

        $guard = new JwtGuard($jwt);

        $this->expectException(RuntimeException::class);
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
