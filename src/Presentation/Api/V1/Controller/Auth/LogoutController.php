<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Auth;

use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * `POST /api/v1/auth/logout`
 *
 * Revokes the current device's refresh token. Equivalent to
 * `DELETE /api/v1/mobile/tokens/{id}` except the device id is derived
 * from the Bearer JWT's `sub` claim instead of being supplied in the URL.
 *
 * Idempotent: a token row that is already revoked is left untouched and
 * the response is still `204`. A missing token row (revoked then later
 * reaped) likewise returns `204` rather than `404` — the access token is
 * already invalid client-side, so surfacing the row's lifecycle to the
 * client would only confuse the logout UX.
 *
 * Error contract:
 *   - 401 `SASO-AUTH-1004` — `Authorization` header missing, malformed,
 *     or carrying an unverifiable / expired JWT (this is the existing
 *     {@see \Saso\Domain\Auth\Exception\AuthRequiredException} contract —
 *     the spec's PR-A3 description names this slot "SASO-AUTH-1005" but
 *     the catalogue already owned 1004 for the same condition; we reuse
 *     it rather than mint a duplicate code)
 */
final class LogoutController
{
    public function __construct(
        private readonly JwtGuard $jwtGuard,
        private readonly DeviceTokenRepository $tokens,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $claims = $this->jwtGuard->authenticate($request);

        $token = $this->tokens->findById($claims->deviceId);
        if ($token === null) {
            // Already revoked + reaped, or never existed. Idempotent 204.
            return new JsonResponse(status: 204, body: []);
        }

        if (!$token->revoked) {
            $this->tokens->save($token->revoke());
        }

        return new JsonResponse(status: 204, body: []);
    }
}
