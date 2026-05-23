<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Auth;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Auth\Exception\AuthRequiredException;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Presentation\Api\V1\Controller\Auth\LogoutController;
use Saso\Presentation\Api\V1\HttpRequest;

final class LogoutControllerTest extends TestCase
{
    private const JWT_SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private JwtService $jwt;

    protected function setUp(): void
    {
        $this->jwt = new JwtService(self::JWT_SECRET);
    }

    public function testLogoutWithValidBearerRevokesToken(): void
    {
        $token = $this->makeDeviceToken(id: 7);
        $repo  = $this->createMock(DeviceTokenRepository::class);
        $repo->method('findById')->with(7)->willReturn($token);

        $saved = null;
        $repo->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (DeviceToken $t) use (&$saved): DeviceToken {
                $saved = $t;
                return $t;
            });

        $controller = new LogoutController(new JwtGuard($this->jwt), $repo);
        $response   = $controller->handle($this->makeRequestWithBearer($this->jwt->issue(7)['token']));

        self::assertSame(204, $response->status);
        self::assertNotNull($saved);
        self::assertTrue($saved->revoked);
    }

    public function testLogoutIsIdempotentWhenTokenAlreadyRevoked(): void
    {
        $revoked = $this->makeDeviceToken(id: 7)->revoke();
        $repo    = $this->createMock(DeviceTokenRepository::class);
        $repo->method('findById')->with(7)->willReturn($revoked);

        // No save() call on an already-revoked token.
        $repo->expects(self::never())->method('save');

        $controller = new LogoutController(new JwtGuard($this->jwt), $repo);
        $response   = $controller->handle($this->makeRequestWithBearer($this->jwt->issue(7)['token']));

        self::assertSame(204, $response->status);
    }

    public function testLogoutReturns204WhenTokenRowMissing(): void
    {
        $repo = $this->createMock(DeviceTokenRepository::class);
        $repo->method('findById')->with(99)->willReturn(null);
        $repo->expects(self::never())->method('save');

        $controller = new LogoutController(new JwtGuard($this->jwt), $repo);
        $response   = $controller->handle($this->makeRequestWithBearer($this->jwt->issue(99)['token']));

        self::assertSame(204, $response->status);
    }

    public function testLogoutWithoutAuthorizationHeaderThrowsAuthRequired(): void
    {
        $repo = $this->createMock(DeviceTokenRepository::class);
        $repo->expects(self::never())->method('findById');

        $controller = new LogoutController(new JwtGuard($this->jwt), $repo);

        $this->expectException(AuthRequiredException::class);
        $controller->handle(new HttpRequest(method: 'POST', path: '/api/v1/auth/logout'));
    }

    public function testLogoutWithMalformedBearerThrowsAuthRequired(): void
    {
        $repo = $this->createMock(DeviceTokenRepository::class);
        $repo->expects(self::never())->method('findById');

        $controller = new LogoutController(new JwtGuard($this->jwt), $repo);

        $this->expectException(AuthRequiredException::class);
        $controller->handle($this->makeRequestWithBearer('not.a.valid.jwt'));
    }

    private function makeDeviceToken(int $id): DeviceToken
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return new DeviceToken(
            id: $id,
            tokenHash: hash('sha256', 'seed_'.$id),
            refreshTokenHash: hash('sha256', 'refresh_'.$id),
            deviceName: 'Test device',
            revoked: false,
            lastUsedAt: null,
            expiresAt: $now->modify('+30 days'),
            createdAt: $now,
            memberId: 'alice12345',
            scopes: DeviceToken::DEFAULT_SCOPES,
        );
    }

    private function makeRequestWithBearer(string $token): HttpRequest
    {
        return new HttpRequest(
            method: 'POST',
            path: '/api/v1/auth/logout',
            headers: ['authorization' => 'Bearer '.$token],
        );
    }
}
