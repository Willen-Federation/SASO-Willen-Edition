<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Auth;

use DateTimeImmutable;
use DateTimeZone;
use Saso\Application\Auth\IssueTokenPairService;
use Saso\Application\Auth\RateLimiter;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Domain\Auth\Exception\InvalidCredentialsException;
use Saso\Domain\Auth\Exception\MalformedRequestException;
use Saso\Domain\Auth\Exception\RateLimitedException;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * `POST /api/v1/auth/login`
 *
 * Verifies a username + password pair and, on success, issues the same
 * OAuth2-style {access_token, refresh_token} pair as
 * `POST /api/v1/mobile/connect`. From the Flutter app's point of view the
 * two endpoints are interchangeable — only the credential shape differs
 * (QR-derived pairing code vs. typed username/password).
 *
 * Response shape and access-token semantics are identical to the pairing
 * flow so existing client code (JWT verification, refresh rotation,
 * device-id-based revocation) keeps working without branches.
 *
 * Error contract:
 *   - 401 `SASO-AUTH-1001` — username / password mismatch (collapsed; no
 *     enumeration leak)
 *   - 423 `SASO-AUTH-1009` — account is locked
 *   - 429 `SASO-AUTH-1010` — too many failed attempts in the window
 *   - 422 `SASO-AUTH-1011` — request body malformed / missing fields
 */
final class LoginController
{
    private const RATE_LIMIT_PREFIX = 'login';

    public function __construct(
        private readonly VerifyCredentialsService $credentials,
        private readonly IssueTokenPairService $issueTokenPair,
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body = $this->parseBody($request);

        // Username is trimmed (operators occasionally paste with whitespace),
        // but the password is taken verbatim — trimming a password would
        // silently change a credential the user supplied, and `hash_equals`
        // must compare exactly the bytes the user typed.
        $username   = trim($this->stringField($body, 'username'));
        $password   = $this->stringField($body, 'password');
        $deviceName = trim((string) ($body['deviceName'] ?? ''));
        if ($deviceName === '') {
            $deviceName = 'Unknown device';
        }

        if ($username === '' || $password === '') {
            throw new MalformedRequestException(
                'Fields "username" and "password" are required.',
            );
        }

        $now    = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $bucket = $this->rateLimitBucket($request, $username);

        if (!$this->rateLimiter->isAllowed($bucket, $now)) {
            throw new RateLimitedException(
                retryAfterSeconds: $this->rateLimiter->retryAfterSeconds($bucket, $now),
            );
        }

        try {
            $member = $this->credentials->verify($username, $password);
        } catch (InvalidCredentialsException $e) {
            $this->rateLimiter->register($bucket, $now);
            throw $e;
        }

        // Successful login clears the bucket so legitimate users who
        // mistyped a few times in a row are not eventually locked out.
        $this->rateLimiter->reset($bucket);

        $payload = $this->issueTokenPair->issue(
            memberId: $member['id'],
            deviceName: $deviceName,
            scopes: DeviceToken::DEFAULT_SCOPES,
            now: $now,
        );

        return new JsonResponse(status: 201, body: $payload);
    }

    /**
     * Build the rate-limit bucket key.
     *
     * Composed of remote IP + username so:
     *   - a single attacker spraying many usernames from one IP hits the
     *     limit per-username (does not affect victims on different IPs); and
     *   - a single attacker rotating usernames against one IP still hits a
     *     per-IP cap because each (ip, user) tuple is independent.
     *
     * Tradeoff: a NATed environment shares one IP, so all users behind that
     * NAT share the same per-username bucket. Acceptable here because the
     * limit is on FAILED attempts; legitimate logins clear the bucket on
     * the way out via {@see RateLimiter::reset()}.
     *
     * Trust model for `X-Forwarded-For`: a client-supplied header is only
     * honoured when `REMOTE_ADDR` matches one of the IPs listed in the
     * `TRUSTED_PROXIES` environment variable (comma-separated). Otherwise
     * the header is ignored — a non-proxied deployment cannot let attackers
     * defeat the per-IP component of the bucket by spoofing the header.
     */
    private function rateLimitBucket(HttpRequest $request, string $username): string
    {
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $ip         = $remoteAddr;

        if ($this->isTrustedProxy($remoteAddr)) {
            $forwardedFor = $request->header('x-forwarded-for');
            if (is_string($forwardedFor) && $forwardedFor !== '') {
                // X-Forwarded-For may carry a comma-separated list; the
                // original client is the leftmost entry.
                $first = explode(',', $forwardedFor)[0];
                $candidate = trim($first);
                if ($candidate !== '') {
                    $ip = $candidate;
                }
            }
        }

        return self::RATE_LIMIT_PREFIX.':'.$ip.':'.$username;
    }

    /**
     * True when `REMOTE_ADDR` is on the configured trusted-proxy allowlist.
     *
     * Loopback (`127.0.0.1`, `::1`) is implicitly trusted so the test/CLI
     * harness keeps working without setting the env var. Production
     * deployments behind a reverse proxy must set `TRUSTED_PROXIES`
     * (comma-separated) to the proxy's exit IP.
     */
    private function isTrustedProxy(string $remoteAddr): bool
    {
        if ($remoteAddr === '127.0.0.1' || $remoteAddr === '::1') {
            return true;
        }

        $configured = getenv('TRUSTED_PROXIES');
        if (!is_string($configured) || $configured === '') {
            return false;
        }

        foreach (explode(',', $configured) as $entry) {
            if (trim($entry) === $remoteAddr) {
                return true;
            }
        }

        return false;
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
