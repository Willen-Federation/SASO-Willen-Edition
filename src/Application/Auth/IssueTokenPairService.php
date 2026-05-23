<?php

declare(strict_types=1);

namespace Saso\Application\Auth;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;

/**
 * Shared issuance of the OAuth2-style {access_token, refresh_token} pair.
 *
 * Extracted from {@see \Saso\Presentation\Api\V1\Controller\Mobile\ConnectController}
 * so both the QR pairing path (`/api/v1/mobile/connect`) and the
 * username-password path (`/api/v1/auth/login`) emit the same payload:
 *
 *   - a stateless 1-hour HS256 JWT access token, and
 *   - a 1-year opaque refresh token (stored only as SHA-256 hash on the
 *     paired `device_token` row).
 *
 * The service is also the right place to define future side effects shared
 * by every issuance call site (audit log entry, "new device" notification),
 * so changes do not have to be made twice.
 *
 * The mobile pairing path keeps its current behaviour byte-for-byte after
 * being refactored to delegate to this service — see the ConnectController
 * tests that still pass unchanged.
 */
final class IssueTokenPairService
{
    public function __construct(
        private readonly DeviceTokenRepository $tokens,
        private readonly JwtService $jwt,
    ) {
    }

    /**
     * Issue a fresh token pair for the given member.
     *
     * @param string|null $memberId `Member.id` minting the token. Null is
     *                              accepted for backward compatibility
     *                              with pre-M3 pairing codes that did not
     *                              carry the minting admin's id; new
     *                              callers should always pass a value
     * @param string $deviceName Human-readable label to record on the
     *                           device_token row (so the admin token
     *                           list can distinguish sessions)
     * @param list<string> $scopes Defaults to {@see DeviceToken::DEFAULT_SCOPES}
     *                             when empty
     *
     * @return array{
     *   access_token: string,
     *   token_type: string,
     *   expires_in: int,
     *   refresh_token: string,
     *   device_id: int,
     *   device_name: string,
     *   expires_at: string,
     * }
     */
    public function issue(
        ?string $memberId,
        string $deviceName,
        array $scopes = [],
        ?DateTimeImmutable $now = null,
    ): array {
        $now      = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $effective = $scopes === [] ? DeviceToken::DEFAULT_SCOPES : array_values($scopes);
        $effectiveMemberId = $memberId === '' ? null : $memberId;

        $rawRefreshToken = DeviceToken::generateRawToken();
        $rawAccessSeed   = DeviceToken::generateRawToken();

        $deviceToken = new DeviceToken(
            id: $this->tokens->nextId(),
            tokenHash: DeviceToken::hashToken($rawAccessSeed),
            refreshTokenHash: DeviceToken::hashToken($rawRefreshToken),
            deviceName: $deviceName,
            revoked: false,
            lastUsedAt: null,
            expiresAt: $now->modify(sprintf('+%d days', DeviceToken::TTL_DAYS)),
            createdAt: $now,
            memberId: $effectiveMemberId,
            scopes: $effective,
        );

        $saved = $this->tokens->save($deviceToken);

        $jwtResult = $this->jwt->issue(
            $saved->id,
            $now,
            $saved->memberId,
            $saved->scopes,
        );

        return [
            'access_token'  => $jwtResult['token'],
            'token_type'    => 'Bearer',
            'expires_in'    => JwtService::ACCESS_TOKEN_TTL_SECONDS,
            'refresh_token' => $rawRefreshToken,
            'device_id'     => $saved->id,
            'device_name'   => $saved->deviceName,
            'expires_at'    => $jwtResult['expiresAt']->format(DateTimeInterface::RFC3339),
        ];
    }
}
