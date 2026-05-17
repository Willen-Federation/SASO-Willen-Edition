<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\MobileConnect\Jwt;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\MobileConnect\Jwt\JwtClaims;
use Saso\Domain\MobileConnect\Jwt\JwtService;

final class JwtServiceTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    public function testRejectsShortSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtService('short');
    }

    public function testIssueAndVerifyRoundTripsDeviceId(): void
    {
        $jwt    = new JwtService(self::SECRET);
        $now    = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
        $result = $jwt->issue(42, $now);

        $claims = $jwt->verify($result['token'], $now);

        self::assertInstanceOf(JwtClaims::class, $claims);
        self::assertSame(42, $claims->deviceId);
        self::assertNull($claims->memberId);
        self::assertSame([], $claims->scopes);
    }

    public function testIssueEmbedsMemberIdAndScopes(): void
    {
        $jwt    = new JwtService(self::SECRET);
        $now    = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
        $result = $jwt->issue(7, $now, 'admin_test', ['items:read', 'items:write']);

        $claims = $jwt->verify($result['token'], $now);

        self::assertSame(7, $claims->deviceId);
        self::assertSame('admin_test', $claims->memberId);
        self::assertSame(['items:read', 'items:write'], $claims->scopes);
        self::assertTrue($claims->hasScope('items:write'));
        self::assertFalse($claims->hasScope('verification:write'));
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        $jwt = new JwtService(self::SECRET);
        $now = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
        $tok = $jwt->issue(1, $now);

        $later = $now->modify('+2 hours');

        $this->expectException(\RuntimeException::class);
        $jwt->verify($tok['token'], $later);
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $jwt = new JwtService(self::SECRET);
        $now = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));
        $tok = $jwt->issue(1, $now);

        $tampered = preg_replace('/[A-Za-z0-9_-]+$/', 'aaaa', $tok['token']);

        $this->expectException(\RuntimeException::class);
        $jwt->verify((string) $tampered, $now);
    }
}
