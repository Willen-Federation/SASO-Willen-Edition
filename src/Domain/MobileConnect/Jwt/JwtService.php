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
 *
 * SASO-specific claims:
 *   mid  - issuing admin's Member.id (string; null only for legacy tokens)
 *   scp  - list of OAuth2-style scopes granted at issuance time
 */
final class JwtService
{
    public const ACCESS_TOKEN_TTL_SECONDS = 3600;

    private const ALGO   = 'sha256';
    private const HEADER = '{"alg":"HS256","typ":"JWT"}';

    /**
     * Canonical issuer asserted at both issuance and verification time.
     *
     * Tokens minted by foreign systems that happen to share JWT_SECRET (e.g.
     * an unrelated tool reusing APP_KEY) cannot impersonate the mobile API
     * because their iss claim will not match. RFC 8725 §3.6 recommends an
     * explicit issuer check whenever the verifier accepts tokens that could
     * have been minted by a different party.
     */
    public const ISSUER = 'saso';

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
     * @param list<string> $scopes
     *
     * @return array{token: string, expiresAt: DateTimeImmutable}
     */
    public function issue(
        int $deviceTokenId,
        ?DateTimeImmutable $now = null,
        ?string $memberId = null,
        array $scopes = [],
    ): array {
        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $exp = $now->modify(sprintf('+%d seconds', self::ACCESS_TOKEN_TTL_SECONDS));

        $claims = [
            'iss' => self::ISSUER,
            'sub' => (string) $deviceTokenId,
            'iat' => $now->getTimestamp(),
            'exp' => $exp->getTimestamp(),
            'jti' => bin2hex(random_bytes(16)),
        ];
        if ($memberId !== null && $memberId !== '') {
            $claims['mid'] = $memberId;
        }
        if ($scopes !== []) {
            $claims['scp'] = array_values($scopes);
        }

        $payload = json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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
     * Verify the token signature and expiry; return the parsed claims.
     *
     * @throws RuntimeException on invalid structure, bad signature, or expiry
     */
    public function verify(string $token, ?DateTimeImmutable $now = null): JwtClaims
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('JWT: malformed token.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        // Reject anything that isn't HS256 before we even look at the body.
        // RFC 8725 §3.1 — the verifier must pin the algorithm rather than
        // trust the token's own header. This closes the "alg: none" /
        // "alg: HS256 vs RS256 confusion" classes of attack pre-emptively if
        // the codebase ever grows additional signing options.
        $headerJson = base64_decode(strtr($headerB64, '-_', '+/'), true);
        if ($headerJson === false) {
            throw new RuntimeException('JWT: header base64 decode failed.');
        }
        $header = json_decode($headerJson, associative: true);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('JWT: unsupported algorithm; HS256 required.');
        }

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

        // Defence in depth: tokens minted by another service that happens to
        // share JWT_SECRET (e.g. APP_KEY reused by a sibling tool) must not
        // be accepted as mobile API tokens.
        if (!isset($claims['iss']) || $claims['iss'] !== self::ISSUER) {
            throw new RuntimeException('JWT: invalid issuer.');
        }

        if (!isset($claims['sub']) || !is_numeric($claims['sub'])) {
            throw new RuntimeException('JWT: missing or invalid sub claim.');
        }

        $memberId = null;
        if (isset($claims['mid']) && is_string($claims['mid']) && $claims['mid'] !== '') {
            $memberId = $claims['mid'];
        }

        $scopes = [];
        if (isset($claims['scp']) && is_array($claims['scp'])) {
            $scopes = array_values(array_filter($claims['scp'], 'is_string'));
        }

        return new JwtClaims(
            deviceId: (int) $claims['sub'],
            memberId: $memberId,
            scopes: $scopes,
        );
    }

    private static function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
