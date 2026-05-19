<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Mobile;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Exception\MobileInvalidRequestException;
use Saso\Domain\MobileConnect\Exception\PairingCodeExpiredException;
use Saso\Domain\MobileConnect\Exception\PairingCodeNotFoundException;
use Saso\Domain\MobileConnect\Exception\PairingCodeUsedException;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Domain\MobileConnect\Repository\PairingCodeRepository;
use Saso\Presentation\Api\V1\Controller\Mobile\ConnectController;
use Saso\Presentation\Api\V1\HttpRequest;

/**
 * Unit tests for POST /api/v1/mobile/connect.
 *
 * Verifies that ConnectController correctly exchanges a valid pairing
 * code for a token pair, propagates memberId from the code into the
 * issued DeviceToken, and raises the right domain exceptions on error
 * inputs (which ProblemExceptionHandler maps to HTTP 4xx in production).
 */
final class ConnectControllerTest extends TestCase
{
    private const JWT_SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private PairingCodeRepository&MockObject $codes;
    private DeviceTokenRepository&MockObject $tokens;
    private JwtService $jwt;
    private ConnectController $controller;

    protected function setUp(): void
    {
        $this->codes  = $this->createMock(PairingCodeRepository::class);
        $this->tokens = $this->createMock(DeviceTokenRepository::class);
        $this->jwt    = new JwtService(self::JWT_SECRET);

        $this->controller = new ConnectController(
            $this->codes,
            $this->tokens,
            $this->jwt,
        );
    }

    public function testSuccessfulConnect_returns201WithTokenPair(): void
    {
        $rawToken = PairingCode::generateRawToken();
        $code     = $this->makeCode($rawToken, memberId: 'member_42');

        $this->codes->method('findByTokenHash')->willReturn($code);
        $this->codes->method('save')->willReturn($code->markUsed());
        $this->tokens->method('nextId')->willReturn(7);
        $this->tokens->method('save')->willReturnCallback(fn (DeviceToken $t) => $t);

        $request  = $this->makeRequest(['token' => $rawToken, 'deviceName' => 'Test Phone']);
        $response = $this->controller->handle($request);

        self::assertSame(201, $response->status);
        self::assertArrayHasKey('access_token', $response->body);
        self::assertArrayHasKey('refresh_token', $response->body);
        self::assertArrayHasKey('device_id', $response->body);
        self::assertSame('Bearer', $response->body['token_type']);
        self::assertIsString($response->body['access_token']);
        self::assertIsString($response->body['refresh_token']);
        self::assertIsInt($response->body['device_id']);
    }

    public function testSuccessfulConnect_propagatesMemberIdToDeviceToken(): void
    {
        $rawToken = PairingCode::generateRawToken();
        $code     = $this->makeCode($rawToken, memberId: 'member_99');

        $this->codes->method('findByTokenHash')->willReturn($code);
        $this->codes->method('save')->willReturn($code->markUsed());
        $this->tokens->method('nextId')->willReturn(3);

        $captured = null;
        $this->tokens
            ->method('save')
            ->willReturnCallback(function (DeviceToken $t) use (&$captured): DeviceToken {
                $captured = $t;
                return $t;
            });

        $this->controller->handle($this->makeRequest(['token' => $rawToken, 'deviceName' => 'Test']));

        self::assertNotNull($captured);
        self::assertSame('member_99', $captured->memberId);
    }

    public function testConnect_throwsOnMissingToken(): void
    {
        $this->expectException(MobileInvalidRequestException::class);

        $this->controller->handle($this->makeRequest(['deviceName' => 'Test']));
    }

    public function testConnect_throwsOnMissingDeviceName(): void
    {
        $this->expectException(MobileInvalidRequestException::class);

        $this->controller->handle($this->makeRequest(['token' => 'abc']));
    }

    public function testConnect_throwsOnUnknownToken(): void
    {
        $this->codes->method('findByTokenHash')->willReturn(null);

        $this->expectException(PairingCodeNotFoundException::class);

        $this->controller->handle($this->makeRequest(['token' => 'no_such_token', 'deviceName' => 'Test']));
    }

    public function testConnect_throwsOnUsedCode(): void
    {
        $rawToken = PairingCode::generateRawToken();
        $code     = $this->makeCode($rawToken, used: true);

        $this->codes->method('findByTokenHash')->willReturn($code);

        $this->expectException(PairingCodeUsedException::class);

        $this->controller->handle($this->makeRequest(['token' => $rawToken, 'deviceName' => 'Test']));
    }

    public function testConnect_throwsOnExpiredCode(): void
    {
        $rawToken = PairingCode::generateRawToken();
        $code     = $this->makeCode($rawToken, expiredAt: new DateTimeImmutable('-1 minute', new DateTimeZone('UTC')));

        $this->codes->method('findByTokenHash')->willReturn($code);

        $this->expectException(PairingCodeExpiredException::class);

        $this->controller->handle($this->makeRequest(['token' => $rawToken, 'deviceName' => 'Test']));
    }

    public function testConnect_emitsDeviceName(): void
    {
        $rawToken = PairingCode::generateRawToken();
        $code     = $this->makeCode($rawToken);

        $this->codes->method('findByTokenHash')->willReturn($code);
        $this->codes->method('save')->willReturn($code->markUsed());
        $this->tokens->method('nextId')->willReturn(1);

        $captured = null;
        $this->tokens->method('save')->willReturnCallback(function (DeviceToken $t) use (&$captured): DeviceToken {
            $captured = $t;
            return $t;
        });

        $this->controller->handle($this->makeRequest(['token' => $rawToken, 'deviceName' => 'iPad mini']));

        self::assertNotNull($captured);
        self::assertSame('iPad mini', $captured->deviceName);
    }

    // -------------------------------------------------------------------------

    private function makeCode(
        string $rawToken,
        bool $used = false,
        ?DateTimeImmutable $expiredAt = null,
        ?string $memberId = null,
    ): PairingCode {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return new PairingCode(
            id: 1,
            tokenHash: PairingCode::hashToken($rawToken),
            label: 'Test pairing',
            used: $used,
            expiresAt: $expiredAt ?? $now->modify('+10 minutes'),
            createdAt: $now,
            memberId: $memberId,
        );
    }

    /** @param array<string, mixed> $body */
    private function makeRequest(array $body): HttpRequest
    {
        return new HttpRequest(
            method: 'POST',
            path: '/api/v1/mobile/connect',
            headers: ['content-type' => 'application/json'],
            body: json_encode($body),
        );
    }
}
