<?php

declare(strict_types=1);

namespace Saso\Application\Mobile;

use RuntimeException;
use Saso\Domain\MobileConnect\Exception\ScopeInsufficientException;
use Saso\Domain\MobileConnect\Jwt\JwtClaims;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Presentation\Api\V1\HttpRequest;

/**
 * Validates the Bearer JWT in an API request and returns the parsed claims.
 *
 * Usage:
 *
 *   $claims = $guard->requireScope($request, 'items:write');
 *
 * Endpoints that read also call `requireScope()` — pass the read scope they
 * expect. The bare {@see authenticate()} method is kept for the few callers
 * that genuinely do not require a scope (e.g. token-introspection endpoints)
 * but every controller that handles data should declare its scope via
 * `requireScope()`. RFC 6749 §3.3: scopes are normative, not advisory.
 *
 * Both methods throw on failure; the global ProblemExceptionHandler renders
 * the response (401 for auth failure, 403 for scope failure).
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

    /**
     * Verify the Bearer token and enforce that the token carries the named scope.
     *
     * Tokens without the scope are refused with
     * {@see ScopeInsufficientException} (→ 403 via ProblemExceptionHandler).
     * The default-paired mobile token carries the union of read+write scopes,
     * so this is invisible for the standard QR flow; tokens issued with a
     * restricted scope set will be refused.
     *
     * @throws RuntimeException when auth fails (→ 401)
     * @throws ScopeInsufficientException when the scope is missing (→ 403)
     */
    public function requireScope(HttpRequest $request, string $scope): JwtClaims
    {
        $claims = $this->authenticate($request);

        if (!$claims->hasScope($scope)) {
            throw new ScopeInsufficientException($scope);
        }

        return $claims;
    }
}
