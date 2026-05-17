<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Exception\DeviceTokenExpiredException;
use Saso\Domain\MobileConnect\Exception\DeviceTokenNotFoundException;
use Saso\Domain\MobileConnect\Exception\DeviceTokenRevokedException;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * POST /api/v1/mobile/token/refresh
 *
 * Exchanges an opaque refresh token for a new JWT access token and a
 * rotated refresh token. Refresh token rotation means each refresh token
 * is single-use — the old one is invalidated and a fresh one is issued.
 *
 * The Flutter app MUST persist the new refresh_token returned here and
 * discard the old one. If the old token is replayed the server will
 * return 400 (not found).
 *
 * Body (JSON):
 *   refresh_token  string  — opaque refresh token from POST /mobile/connect
 *                            or from the previous refresh call
 *
 * Response (RFC 6749 §5.1 token endpoint shape):
 *   access_token   string  — new JWT Bearer token (1 hour)
 *   token_type     string  — always "Bearer"
 *   expires_in     int     — access token TTL in seconds
 *   refresh_token  string  — rotated refresh token (1 year from issuance)
 *   device_id      int
 *   expires_at     string  — access token expiry, RFC 3339
 */
final class TokenRefreshController
{
    public function __construct(
        private readonly DeviceTokenRepository $tokens,
        private readonly JwtService $jwt,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body        = $this->parseBody($request);
        $rawRefresh  = trim((string) ($body['refresh_token'] ?? ''));

        if ($rawRefresh === '') {
            throw new \InvalidArgumentException('Field "refresh_token" is required.');
        }

        $hash  = DeviceToken::hashToken($rawRefresh);
        $token = $this->tokens->findByRefreshTokenHash($hash);

        if ($token === null) {
            throw new DeviceTokenNotFoundException();
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($token->revoked) {
            throw new DeviceTokenRevokedException();
        }

        if ($token->isExpired($now)) {
            throw new DeviceTokenExpiredException();
        }

        $newRawRefresh = DeviceToken::generateRawToken();
        $rotated       = new DeviceToken(
            id: $token->id,
            tokenHash: $token->tokenHash,
            refreshTokenHash: DeviceToken::hashToken($newRawRefresh),
            deviceName: $token->deviceName,
            revoked: false,
            lastUsedAt: $now,
            expiresAt: $token->expiresAt,
            createdAt: $token->createdAt,
            memberId: $token->memberId,
            scopes: $token->scopes,
        );

        $this->tokens->save($rotated);

        $jwtResult = $this->jwt->issue(
            $token->id,
            $now,
            $token->memberId,
            $token->scopes,
        );

        return new JsonResponse(
            status: 200,
            body: [
                'access_token'  => $jwtResult['token'],
                'token_type'    => 'Bearer',
                'expires_in'    => JwtService::ACCESS_TOKEN_TTL_SECONDS,
                'refresh_token' => $newRawRefresh,
                'device_id'     => $token->id,
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
