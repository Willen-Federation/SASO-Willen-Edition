<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Jwt;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

/**
 * Minimal HS256 JWT service — no external library dependency.
 *
 * Issues short-lived access tokens (default 1 hour) signed with a
 * 256-bit secret derived from the application's APP_KEY or JWT_SECRET
 * environment variable. Tokens are stateless: the server does not
 * store them. Revocation is enforced only through the paired refresh
 * token (stored as SHA-256 hash in `device_token.refresh_token_hash`).
 *
 * Wire format:  base64url(header).base64url(payload).base64url(sig)
 * Algorithm:    HMAC-SHA256 (alg = "HS256")
 *
 * Standard claims used:
 *   iss  - issuer ("saso")
 *   sub  - device_token.id as string
 *   iat  - issued-at (Unix timestamp)
 *   exp  - expiry   (Unix timestamp)
 *   jti  - unique token id (prevents trivial replay if needed)
 */
final class JwtService
{
    public const ACCESS_TOKEN_TTL_SECONDS = 3600;

    private const ALGO   = 'sha256';
    private const HEADER = '{"alg":"HS256","typ":"JWT"}';

    public function __construct(
        private readonly string $secret,
    ) {
        if (strlen($this->secret) < 32) {
            throw new InvalidArgumentException('JwtService secret must be at least 32 bytes.');
        }
    }

    /**
     * Issue a signed JWT for the given device token ID.
     *
     * @return array{token: string, expiresAt: DateTimeImmutable}
     */
    public function issue(int $deviceTokenId, ?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $exp = $now->modify(sprintf('+%d seconds', self::ACCESS_TOKEN_TTL_SECONDS));

        $payload = json_encode([
            'iss' => 'saso',
            'sub' => (string) $deviceTokenId,
            'iat' => $now->getTimestamp(),
            'exp' => $exp->getTimestamp(),
            'jti' => bin2hex(random_bytes(16)),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new RuntimeException('JwtService: json_encode failed.');
        }

        $headerB64  = self::b64u(self::HEADER);
        $payloadB64 = self::b64u($payload);
        $sig        = self::b64u(hash_hmac(self::ALGO, $headerB64.'.'.$payloadB64, $this->secret, true));

        return [
            'token'     => $headerB64.'.'.$payloadB64.'.'.$sig,
            'expiresAt' => $exp,
        ];
    }

    /**
     * Verify the token signature and expiry; return the `sub` claim (device token ID).
     *
     * @throws RuntimeException on invalid structure, bad signature, or expiry
     */
    public function verify(string $token, ?DateTimeImmutable $now = null): int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('JWT: malformed token.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $expected = self::b64u(hash_hmac(self::ALGO, $headerB64.'.'.$payloadB64, $this->secret, true));

        if (!hash_equals($expected, $sigB64)) {
            throw new RuntimeException('JWT: invalid signature.');
        }

        $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'), true);
        if ($payloadJson === false) {
            throw new RuntimeException('JWT: payload base64 decode failed.');
        }

        $claims = json_decode($payloadJson, associative: true);
        if (!is_array($claims)) {
            throw new RuntimeException('JWT: payload JSON decode failed.');
        }

        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!isset($claims['exp']) || $now->getTimestamp() > (int) $claims['exp']) {
            throw new RuntimeException('JWT: token has expired.');
        }

        if (!isset($claims['sub']) || !is_numeric($claims['sub'])) {
            throw new RuntimeException('JWT: missing or invalid sub claim.');
        }

        return (int) $claims['sub'];
    }

    private static function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
