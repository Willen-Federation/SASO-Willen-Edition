<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Auth;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Saso\Application\Auth\IssueTokenPairService;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;

/**
 * Verifies the shared token-pair issuance service behaves identically to
 * the inline logic that previously lived inside ConnectController.
 */
final class IssueTokenPairServiceTest extends TestCase
{
    private const JWT_SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function testIssueReturnsExpectedPayloadShape(): void
    {
        $captured = null;
        $repo     = $this->makeRepo(nextId: 7, capture: $captured);
        $service  = new IssueTokenPairService($repo, new JwtService(self::JWT_SECRET));

        $payload = $service->issue(
            memberId: 'alice',
            deviceName: 'Test Phone',
            scopes: ['items:read'],
        );

        self::assertSame('Bearer', $payload['token_type']);
        self::assertSame(JwtService::ACCESS_TOKEN_TTL_SECONDS, $payload['expires_in']);
        self::assertSame(7, $payload['device_id']);
        self::assertSame('Test Phone', $payload['device_name']);
        self::assertIsString($payload['access_token']);
        self::assertIsString($payload['refresh_token']);
        self::assertNotEmpty($payload['expires_at']);

        self::assertNotNull($captured);
        self::assertSame('alice', $captured->memberId);
        self::assertSame(['items:read'], $captured->scopes);
    }

    public function testIssueUsesDefaultScopesWhenEmpty(): void
    {
        $captured = null;
        $repo     = $this->makeRepo(nextId: 1, capture: $captured);
        $service  = new IssueTokenPairService($repo, new JwtService(self::JWT_SECRET));

        $service->issue(memberId: 'bob', deviceName: 'iPad');

        self::assertNotNull($captured);
        self::assertSame(DeviceToken::DEFAULT_SCOPES, $captured->scopes);
    }

    public function testIssueAcceptsNullMemberIdForLegacyCompatibility(): void
    {
        $captured = null;
        $repo     = $this->makeRepo(nextId: 1, capture: $captured);
        $service  = new IssueTokenPairService($repo, new JwtService(self::JWT_SECRET));

        $service->issue(memberId: null, deviceName: 'Old Pair');

        self::assertNotNull($captured);
        self::assertNull($captured->memberId);
    }

    public function testEmptyStringMemberIdIsTreatedAsNull(): void
    {
        $captured = null;
        $repo     = $this->makeRepo(nextId: 1, capture: $captured);
        $service  = new IssueTokenPairService($repo, new JwtService(self::JWT_SECRET));

        $service->issue(memberId: '', deviceName: 'Empty');

        self::assertNotNull($captured);
        self::assertNull($captured->memberId);
    }

    public function testRefreshTokenIsStoredAsHashNotPlaintext(): void
    {
        $captured = null;
        $repo     = $this->makeRepo(nextId: 4, capture: $captured);
        $service  = new IssueTokenPairService($repo, new JwtService(self::JWT_SECRET));

        $payload = $service->issue(memberId: 'alice', deviceName: 'Test');

        self::assertNotNull($captured);
        self::assertSame(
            DeviceToken::hashToken($payload['refresh_token']),
            $captured->refreshTokenHash,
        );
        self::assertNotSame($payload['refresh_token'], $captured->refreshTokenHash);
    }

    public function testExpiresAtIsRfc3339Formatted(): void
    {
        $repo    = $this->makeRepo(nextId: 1);
        $service = new IssueTokenPairService($repo, new JwtService(self::JWT_SECRET));

        $now     = new DateTimeImmutable('2026-05-23T12:00:00', new DateTimeZone('UTC'));
        $payload = $service->issue(
            memberId: 'alice',
            deviceName: 'Test',
            now: $now,
        );

        $expected = $now->modify('+'.JwtService::ACCESS_TOKEN_TTL_SECONDS.' seconds')
            ->format(\DateTimeInterface::RFC3339);
        self::assertSame($expected, $payload['expires_at']);
    }

    private function makeRepo(int $nextId, ?DeviceToken &$capture = null): DeviceTokenRepository
    {
        $mock = $this->createMock(DeviceTokenRepository::class);
        $mock->method('nextId')->willReturn($nextId);
        $mock->method('save')->willReturnCallback(function (DeviceToken $token) use (&$capture): DeviceToken {
            $capture = $token;
            return $token;
        });

        return $mock;
    }
}
