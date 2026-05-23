<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Auth;

use DateTimeImmutable;
use DateTimeZone;
use Saso\Application\Auth\RateLimiter;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Auth\Exception\CurrentPasswordMismatchException;
use Saso\Domain\Auth\Exception\InvalidCredentialsException;
use Saso\Domain\Auth\Exception\MalformedRequestException;
use Saso\Domain\Auth\Exception\PasswordPolicyViolationException;
use Saso\Domain\Auth\Exception\RateLimitedException;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use saso\entity\Member;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * `POST /api/v1/auth/password`
 *
 * Changes the authenticated member's password. Verifies the supplied
 * `currentPassword` against the stored hash (so a stolen access token
 * alone is not enough to rotate credentials), enforces the password
 * policy on `newPassword`, then writes a fresh Argon2id digest.
 *
 * Side effect — revokes every other device's refresh token. This forces
 * any session minted before the change to re-authenticate against the
 * new password. The current device's refresh token is intentionally left
 * untouched so the user is not signed out of the screen on which they
 * just changed the password.
 *
 * Error contract:
 *   - 401 `SASO-AUTH-1004` — Bearer missing / invalid (see
 *     {@see \Saso\Domain\Auth\Exception\AuthRequiredException})
 *   - 401 `SASO-AUTH-1012` — `currentPassword` did not match
 *   - 422 `SASO-AUTH-1011` — request body malformed
 *   - 422 `SASO-AUTH-1013` — `newPassword` fails the password policy
 *   - 429 `SASO-AUTH-1010` — too many failed attempts
 */
final class PasswordChangeController
{
    private const RATE_LIMIT_PREFIX = 'pwchange';

    public function __construct(
        private readonly JwtGuard $jwtGuard,
        private readonly VerifyCredentialsService $credentials,
        private readonly DeviceTokenRepository $tokens,
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $claims   = $this->jwtGuard->authenticate($request);
        $memberId = $claims->memberId;

        if ($memberId === null || $memberId === '') {
            // Legacy tokens minted before the `mid` claim landed have no
            // bound member. Refuse: we cannot identify whose password to
            // change, and we would not want to leak the device→member
            // mapping by guessing from the device row.
            throw new MalformedRequestException(
                'Access token is not bound to a member; cannot change password.',
            );
        }

        $body            = $this->parseBody($request);
        $currentPassword = $this->stringField($body, 'currentPassword');
        $newPassword     = $this->stringField($body, 'newPassword');

        if ($currentPassword === '' || $newPassword === '') {
            throw new MalformedRequestException(
                'Fields "currentPassword" and "newPassword" are required.',
            );
        }

        $now    = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $bucket = self::RATE_LIMIT_PREFIX.':'.$memberId;

        if (!$this->rateLimiter->isAllowed($bucket, $now)) {
            throw new RateLimitedException(
                retryAfterSeconds: $this->rateLimiter->retryAfterSeconds($bucket, $now),
            );
        }

        // Enforce password policy BEFORE verifying the current password.
        // Order matters: a malformed new password is a client bug, not a
        // failed authentication, and surfacing it without consuming a
        // rate-limit slot mirrors the legacy `PasswordController` behaviour.
        $policy = Member::passwordConstraint($newPassword);
        if ($policy->isLeft()) {
            throw new PasswordPolicyViolationException(
                'New password must be 8-64 characters and use only [A-Za-z0-9_-].',
            );
        }
        if ($newPassword === $currentPassword) {
            throw new PasswordPolicyViolationException(
                'New password must differ from the current password.',
            );
        }

        try {
            $member = $this->credentials->verify($memberId, $currentPassword);
        } catch (InvalidCredentialsException) {
            $this->rateLimiter->register($bucket, $now);
            throw new CurrentPasswordMismatchException();
        }

        $this->rateLimiter->reset($bucket);

        $this->credentials->updatePasswordHash($member['id'], $newPassword);

        // Force every other device this member owns to re-authenticate.
        // The current device (identified by JWT `sub`) is preserved so the
        // user is not signed out of the screen they just used.
        $currentDeviceId = $claims->deviceId;
        foreach ($this->tokens->findByMemberId($member['id']) as $deviceToken) {
            if ($deviceToken->id === $currentDeviceId) {
                continue;
            }
            if ($deviceToken->revoked) {
                continue;
            }
            $this->tokens->save($deviceToken->revoke());
        }

        return new JsonResponse(status: 204, body: []);
    }

    /** @param array<string, mixed> $body */
    private function stringField(array $body, string $key): string
    {
        $value = $body[$key] ?? null;
        if ($value === null) {
            return '';
        }
        if (!is_string($value)) {
            throw new MalformedRequestException(sprintf(
                'Field "%s" must be a string.',
                $key,
            ));
        }

        return $value;
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
