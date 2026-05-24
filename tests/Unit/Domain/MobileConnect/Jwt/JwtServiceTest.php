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

    public function testVerifyRejectsAlgNoneHeader(): void
    {
        // RFC 8725 §3.1: the verifier must pin the algorithm. Even though our
        // signature check would also reject this (HMAC over a "none" header
        // doesn't match an empty signature), failing on the header explicitly
        // keeps the contract clear and stops anyone adding a second algorithm
        // later from re-opening the alg-confusion door.
        $jwt = new JwtService(self::SECRET);
        $now = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));

        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header  = $b64('{"alg":"none","typ":"JWT"}');
        $payload = $b64((string) json_encode([
            'iss' => 'saso',
            'sub' => '1',
            'iat' => $now->getTimestamp(),
            'exp' => $now->getTimestamp() + 3600,
        ]));
        $forged = $header.'.'.$payload.'.';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HS256 required');
        $jwt->verify($forged, $now);
    }

    public function testVerifyRejectsRs256Header(): void
    {
        // alg-confusion attack: caller swaps alg to RS256 hoping the verifier
        // will reach into a "public key" code path. Our verifier never does —
        // it pins HS256 — so this must always fail on the header check.
        $jwt = new JwtService(self::SECRET);
        $now = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));

        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header  = $b64('{"alg":"RS256","typ":"JWT"}');
        $payload = $b64((string) json_encode([
            'iss' => 'saso',
            'sub' => '1',
            'iat' => $now->getTimestamp(),
            'exp' => $now->getTimestamp() + 3600,
        ]));
        // Sign body with the secret so the only thing wrong is the header alg.
        $sig = $b64(hash_hmac('sha256', $header.'.'.$payload, self::SECRET, true));
        $forged = $header.'.'.$payload.'.'.$sig;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HS256 required');
        $jwt->verify($forged, $now);
    }

    public function testVerifyRejectsWrongIssuer(): void
    {
        // Defense in depth: a token minted by another service sharing the same
        // secret must not unlock the mobile API. Without an issuer pin, a
        // sibling tool that happens to reuse APP_KEY could mint tokens that
        // satisfy this verifier.
        $jwt = new JwtService(self::SECRET);
        $now = new DateTimeImmutable('2026-05-17 12:00:00', new DateTimeZone('UTC'));

        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header  = $b64('{"alg":"HS256","typ":"JWT"}');
        $payload = $b64((string) json_encode([
            'iss' => 'not-saso',
            'sub' => '1',
            'iat' => $now->getTimestamp(),
            'exp' => $now->getTimestamp() + 3600,
        ]));
        $sig = $b64(hash_hmac('sha256', $header.'.'.$payload, self::SECRET, true));
        $forged = $header.'.'.$payload.'.'.$sig;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid issuer');
        $jwt->verify($forged, $now);
    }
}
