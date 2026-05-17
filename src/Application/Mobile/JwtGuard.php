<?php

declare(strict_types=1);

namespace Saso\Application\Mobile;

use RuntimeException;
use Saso\Domain\MobileConnect\Jwt\JwtClaims;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Presentation\Api\V1\HttpRequest;

/**
 * Validates the Bearer JWT in an API request and returns the parsed claims.
 *
 * Usage: call `$guard->authenticate($request)` at the top of any controller
 * method that requires an authenticated device. Throws RuntimeException (which
 * ProblemExceptionHandler converts to 401) on missing/invalid/expired tokens.
 */
final class JwtGuard
{
    public function __construct(
        private readonly JwtService $jwt,
    ) {
    }

    /**
     * Extract and verify the Bearer token from the Authorization header.
     *
     * @throws RuntimeException when auth fails (→ 401 via ProblemExceptionHandler)
     */
    public function authenticate(HttpRequest $request): JwtClaims
    {
        $authHeader = $request->header('authorization') ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new RuntimeException('Missing or malformed Authorization header.');
        }

        $token = substr($authHeader, 7);

        return $this->jwt->verify($token);
    }
}
