<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Exception\MobileInvalidRequestException;
use Saso\Domain\MobileConnect\Exception\PairingCodeExpiredException;
use Saso\Domain\MobileConnect\Exception\PairingCodeNotFoundException;
use Saso\Domain\MobileConnect\Exception\PairingCodeUsedException;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Domain\MobileConnect\Repository\PairingCodeRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * POST /api/v1/mobile/connect
 *
 * Exchanges a one-time pairing code (scanned from the QR) for an
 * OAuth2-style token pair:
 *
 *   access_token   short-lived HS256 JWT (1 hour, stateless)
 *   refresh_token  long-lived opaque token (1 year, stored as SHA-256 hash)
 *
 * The Flutter app calls this automatically after recognising the
 * `SASO1:` prefix in the QR payload. No manual user interaction is
 * needed beyond pointing the camera.
 *
 * Body (JSON):
 *   token       string  — raw token extracted from the QR payload after `SASO1:` and before `|`
 *   deviceName  string  — human-readable label (e.g. "iPad mini")
 *
 * Response (OAuth2 token endpoint shape, RFC 6749 §5.1):
 *   access_token   string  — JWT Bearer token, short-lived
 *   token_type     string  — always "Bearer"
 *   expires_in     int     — access token TTL in seconds
 *   refresh_token  string  — opaque refresh token, long-lived
 *   device_id      int     — numeric device record ID (for revocation)
 */
final class ConnectController
{
    public function __construct(
        private readonly PairingCodeRepository $codes,
        private readonly DeviceTokenRepository $tokens,
        private readonly JwtService $jwt,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body       = $this->parseBody($request);
        $rawToken   = trim((string) ($body['token'] ?? ''));
        $deviceName = trim((string) ($body['deviceName'] ?? ''));

        if ($rawToken === '' || $deviceName === '') {
            throw new MobileInvalidRequestException('Fields "token" and "deviceName" are required.');
        }

        $hash = PairingCode::hashToken($rawToken);
        $code = $this->codes->findByTokenHash($hash);

        if ($code === null) {
            throw new PairingCodeNotFoundException();
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($code->used) {
            throw new PairingCodeUsedException();
        }

        if ($code->isExpired($now)) {
            throw new PairingCodeExpiredException();
        }

        $this->codes->save($code->markUsed());

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
            memberId: $code->memberId,
            scopes: DeviceToken::DEFAULT_SCOPES,
        );

        $saved = $this->tokens->save($deviceToken);

        $jwtResult = $this->jwt->issue(
            $saved->id,
            $now,
            $saved->memberId,
            $saved->scopes,
        );

        return new JsonResponse(
            status: 201,
            body: [
                'access_token'  => $jwtResult['token'],
                'token_type'    => 'Bearer',
                'expires_in'    => JwtService::ACCESS_TOKEN_TTL_SECONDS,
                'refresh_token' => $rawRefreshToken,
                'device_id'     => $saved->id,
                'device_name'   => $saved->deviceName,
                'expires_at'    => $jwtResult['expiresAt']->format(DateTimeInterface::RFC3339),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function parseBody(HttpRequest $request): array
    {
        if ($request->body === null || $request->body === '') {
            return [];
        }

        $decoded = json_decode($request->body, associative: true);

        return is_array($decoded) ? $decoded : [];
    }
}
