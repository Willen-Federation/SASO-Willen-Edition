<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Saso\Application\MyPage\Passkey\Auth0ManagementApi;
use Saso\Application\MyPage\Passkey\Auth0ManagementApiException;
use Saso\Application\MyPage\Passkey\Auth0Passkey;
use Throwable;

/**
 * Guzzle-backed {@see Auth0ManagementApi} implementation.
 *
 * The token mint is lazy and per-instance: the first call fetches a
 * client-credentials M2M token from `https://{domain}/oauth/token`, stores
 * it in `$this->cachedToken`, and reuses it for every subsequent request
 * for the lifetime of this instance. SASO request handlers are
 * single-shot, so an in-memory cache is enough — surviving across PHP
 * requests would be a nice-to-have but is left to a follow-up (the M2M
 * token TTL is typically 24h, so reminting per request is wasteful but
 * not broken).
 *
 * Endpoints used:
 *   - POST /oauth/token              (mint M2M access token)
 *   - GET  /api/v2/users/{id}/authentication-methods
 *   - DELETE /api/v2/users/{id}/authentication-methods/{auth_method_id}
 */
final class GuzzleAuth0ManagementApi implements Auth0ManagementApi
{
    /**
     * Auth0 returns a string like `passkey` or `webauthn-roaming` /
     * `webauthn-platform` depending on the registration channel. We treat
     * all three as "passkeys" because the My Page list does not need to
     * distinguish them at the UI level.
     *
     * @var list<string>
     */
    private const PASSKEY_TYPES = [
        'passkey',
        'webauthn-roaming',
        'webauthn-platform',
    ];

    private ?string $cachedToken = null;

    public function __construct(
        private readonly ClientInterface $http,
        private readonly string $domain,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
    }

    public function listPasskeys(string $auth0UserId): array
    {
        $response = $this->call('GET', $this->methodsUrl($auth0UserId));
        $body     = (string) $response->getBody();
        $decoded  = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new Auth0ManagementApiException(
                'Auth0 returned a non-array body for authentication-methods.',
                $response->getStatusCode(),
            );
        }

        $passkeys = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $type = (string) ($entry['type'] ?? '');
            if (!in_array($type, self::PASSKEY_TYPES, true)) {
                continue;
            }
            $passkeys[] = new Auth0Passkey(
                id: (string) ($entry['id'] ?? ''),
                name: (string) ($entry['name'] ?? ''),
                createdAt: self::parseTimestamp($entry['created_at'] ?? null),
                lastUsedAt: self::parseTimestamp($entry['last_auth_at'] ?? null),
            );
        }

        return $passkeys;
    }

    public function deletePasskey(string $auth0UserId, string $authenticationMethodId): void
    {
        if ($authenticationMethodId === '') {
            throw new Auth0ManagementApiException(
                'Refusing to DELETE authentication-methods with an empty id.',
                0,
            );
        }

        try {
            $this->call(
                'DELETE',
                $this->methodsUrl($auth0UserId).'/'.rawurlencode($authenticationMethodId),
            );
        } catch (Auth0ManagementApiException $e) {
            // Treat "already gone" as success (idempotent delete).
            if ($e->upstreamStatus === 404) {
                return;
            }
            throw $e;
        }
    }

    private function call(string $method, string $url): ResponseInterface
    {
        $token = $this->token();
        $request = new Request(
            $method,
            $url,
            [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
                'User-Agent'    => 'saso-willen-passkey/1.0',
            ],
        );

        try {
            $response = $this->http->send($request, ['http_errors' => true]);
        } catch (RequestException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 0;
            throw new Auth0ManagementApiException(
                sprintf('Auth0 %s %s returned HTTP %d.', $method, $url, $status),
                $status,
                $e,
            );
        } catch (ConnectException $e) {
            throw new Auth0ManagementApiException(
                'Auth0 Management API is unreachable: '.$e->getMessage(),
                0,
                $e,
            );
        } catch (GuzzleException $e) {
            throw new Auth0ManagementApiException(
                'Auth0 Management API call failed: '.$e->getMessage(),
                0,
                $e,
            );
        } catch (Throwable $e) {
            throw new Auth0ManagementApiException(
                'Unexpected error during Auth0 Management API call: '.$e->getMessage(),
                0,
                $e,
            );
        }

        return $response;
    }

    private function token(): string
    {
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }

        $payload = json_encode([
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'audience'      => $this->managementAudience(),
        ], JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new Auth0ManagementApiException('Failed to encode M2M token request body.', 0);
        }

        $request = new Request(
            'POST',
            'https://'.$this->domain.'/oauth/token',
            [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'saso-willen-passkey/1.0',
            ],
            $payload,
        );

        try {
            $response = $this->http->send($request, ['http_errors' => true]);
        } catch (RequestException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 0;
            throw new Auth0ManagementApiException(
                'Auth0 M2M token mint failed: HTTP '.$status,
                $status,
                $e,
            );
        } catch (GuzzleException $e) {
            throw new Auth0ManagementApiException(
                'Auth0 M2M token mint failed: '.$e->getMessage(),
                0,
                $e,
            );
        }

        $body    = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $token   = is_array($decoded) ? ($decoded['access_token'] ?? null) : null;

        if (!is_string($token) || $token === '') {
            throw new Auth0ManagementApiException(
                'Auth0 M2M token response did not contain an access_token.',
                $response->getStatusCode(),
            );
        }

        return $this->cachedToken = $token;
    }

    private function methodsUrl(string $auth0UserId): string
    {
        if ($auth0UserId === '') {
            throw new Auth0ManagementApiException(
                'Refusing to call Auth0 Management API with an empty user id.',
                0,
            );
        }
        return 'https://'.$this->domain
            .'/api/v2/users/'.rawurlencode($auth0UserId)
            .'/authentication-methods';
    }

    private function managementAudience(): string
    {
        return 'https://'.$this->domain.'/api/v2/';
    }

    private static function parseTimestamp(mixed $raw): ?DateTimeImmutable
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($raw, new DateTimeZone('UTC'));
        } catch (\Throwable) {
            return null;
        }
    }
}
