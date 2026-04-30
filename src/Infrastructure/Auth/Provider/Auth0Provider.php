<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Saso\Domain\Auth\AuthenticatedIdentity;
use Saso\Domain\Auth\AuthProvider;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\CallbackRequest;
use Saso\Domain\Auth\ClaimMapping;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;
use Saso\Domain\Auth\LoginContext;
use Saso\Domain\Auth\LogoutContext;
use Saso\Domain\Auth\Redirect;
use Saso\Infrastructure\Auth\PhpSessionStore;
use Throwable;

/**
 * Auth0 provider backed by the official auth0/auth0-php v8 SDK.
 *
 * Advantages over the generic jumbojett-based approach:
 *   - PKCE (S256 code challenge) is enabled by default — required by Auth0
 *     Universal Login with the current security settings.
 *   - Token rotation / refresh tokens are handled natively when
 *     `offline_access` is included in scopes.
 *   - Auth0's non-standard `/v2/logout` endpoint is used automatically via
 *     `Auth0::logout()`.
 *
 * Configuration extras (stored under `auth_provider.claim_mapping._config`):
 *   - `domain`    Auth0 tenant host (e.g. `acme.eu.auth0.com`). May also be
 *                 inferred from `issuer_or_metadata_url` if omitted.
 *   - `audience`  Optional API audience to include in access tokens.
 *
 * The `cookieSecret` required by SdkConfiguration is derived from APP_KEY (or
 * the provider's client_id as a fallback). Since we supply PhpSessionStore for
 * both session and transient storage the cookie-encryption path in the SDK is
 * never reached — the secret is only needed to pass the constructor guard.
 */
final class Auth0Provider implements AuthProvider
{
    private readonly string $domain;

    public function __construct(
        private readonly AuthProviderRecord $record,
        private readonly string $callbackUrl,
    ) {
        if ($record->type !== AuthProviderType::Oidc) {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'Auth0Provider requires AuthProviderType::Oidc.',
            );
        }

        $cfg    = $this->config();
        $domain = is_string($cfg['domain'] ?? null) ? trim($cfg['domain']) : '';

        if ($domain === '') {
            $issuer = trim((string) ($record->issuerOrMetadataUrl ?? ''));
            if ($issuer !== '') {
                $host   = parse_url($issuer, PHP_URL_HOST);
                $domain = is_string($host) ? $host : '';
            }
        }

        if ($domain === '') {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'Auth0Provider requires _config.domain or a valid issuer_or_metadata_url.',
            );
        }
        if ((string) ($record->clientId ?? '') === '') {
            throw ProviderMisconfiguredException::for($record->name, 'Auth0Provider requires client_id.');
        }
        if ((string) ($record->clientSecret ?? '') === '') {
            throw ProviderMisconfiguredException::for($record->name, 'Auth0Provider requires client_secret.');
        }

        $this->domain = $domain;
    }

    public function id(): AuthProviderId
    {
        return $this->record->id;
    }

    public function type(): AuthProviderType
    {
        return $this->record->type;
    }

    public function displayName(): string
    {
        return $this->record->name;
    }

    public function supportsLogout(): bool
    {
        return true;
    }

    public function beginLogin(LoginContext $context): Redirect
    {
        $sdk = $this->buildSdk();

        $params = ['state' => $context->csrfStateToken];

        $audience = $this->configString('audience');
        if ($audience !== null && $audience !== '') {
            $params['audience'] = $audience;
        }

        $url = $sdk->login(redirectUrl: $this->callbackUrl, params: $params);

        // Mirror into our own session keys so LoginOrchestrator can read them.
        $_SESSION['auth.state']       = $context->csrfStateToken;
        $_SESSION['auth.provider_id'] = $this->record->id->value;
        $_SESSION['auth.return_to']   = $context->returnTo;

        return new Redirect($url, 302);
    }

    public function completeLogin(CallbackRequest $request): AuthenticatedIdentity
    {
        $expectedState = (string) ($_SESSION['auth.state'] ?? '');
        if ($expectedState === '') {
            throw AuthFailedException::stateMismatch(
                'No pending Auth0 state — session may have expired.',
            );
        }

        $receivedState = (string) ($request->query['state'] ?? '');
        if (!hash_equals($expectedState, $receivedState)) {
            unset($_SESSION['auth.state']);
            throw AuthFailedException::stateMismatch();
        }

        $sdk = $this->buildSdk();

        try {
            $sdk->exchange(redirectUri: $this->callbackUrl);
        } catch (Throwable $e) {
            throw AuthFailedException::callbackInvalid('Auth0 exchange failed: '.$e->getMessage());
        }

        $user = $sdk->getUser();
        if (!is_array($user) || ($user['sub'] ?? '') === '') {
            throw AuthFailedException::callbackInvalid('Auth0 user profile missing sub claim.');
        }

        $mapping = $this->claimMapping();
        $sub     = $mapping->extractString('subject', $user) ?? '';
        $email   = $mapping->extractString('email', $user) ?? '';
        $name    = $mapping->extractString('display_name', $user) ?? $email;

        if ($sub === '') {
            throw AuthFailedException::callbackInvalid('Auth0 ID token did not carry a usable subject.');
        }

        $credentials = $sdk->getCredentials();
        if ($credentials !== null) {
            $idToken = $credentials->idToken ?? null;
            if (is_string($idToken) && $idToken !== '') {
                $_SESSION['auth.id_token'] = $idToken;
            }
        }

        unset($_SESSION['auth.state']);

        return new AuthenticatedIdentity(
            authProviderId: $this->record->id,
            externalSubject: $sub,
            email: $email,
            displayName: $name,
            claims: $user,
        );
    }

    public function beginLogout(LogoutContext $context): ?Redirect
    {
        $sdk = $this->buildSdk();
        try {
            $url = $sdk->logout(returnUri: $context->returnTo);
        } catch (Throwable) {
            return null;
        }
        return new Redirect($url, 302);
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function buildSdk(): Auth0
    {
        $audience = $this->configString('audience');

        return new Auth0(new SdkConfiguration(
            domain: $this->domain,
            clientId: (string) $this->record->clientId,
            clientSecret: (string) $this->record->clientSecret,
            redirectUri: $this->callbackUrl,
            cookieSecret: $this->deriveCookieSecret(),
            sessionStorage: new PhpSessionStore('auth0_s'),
            transientStorage: new PhpSessionStore('auth0_t'),
            scope: $this->scopes(),
            audience: ($audience !== null && $audience !== '') ? [$audience] : null,
            usePkce: true,
        ));
    }

    /**
     * Derives a ≥32-char string required by SdkConfiguration's cookie-secret
     * guard. Since we use PhpSessionStore the secret is never used to encrypt
     * actual cookies — any stable, secret-enough value works here.
     */
    private function deriveCookieSecret(): string
    {
        $appKey = (string) (getenv('APP_KEY') ?: '');
        if ($appKey !== '') {
            $decoded = base64_decode($appKey, true);
            if ($decoded !== false && strlen($decoded) >= 32) {
                return $decoded;
            }
        }
        return hash('sha256', 'saso:auth0:'.(string) $this->record->clientId.':'.$this->record->id->value);
    }

    /** @return list<string> */
    private function scopes(): array
    {
        $raw = trim((string) ($this->record->scopes ?? ''));
        if ($raw === '') {
            return ['openid', 'profile', 'email', 'offline_access'];
        }
        $list = preg_split('/\s+/', $raw) ?: [];
        return array_values(array_filter($list, static fn (string $s): bool => $s !== ''));
    }

    private function claimMapping(): ClaimMapping
    {
        $raw = $this->record->claimMapping ?? [];
        $map = [];
        foreach ($raw as $field => $claim) {
            if ($field === '_config' || !is_string($claim)) {
                continue;
            }
            $map[$field] = $claim;
        }
        return $map === [] ? new ClaimMapping() : ClaimMapping::withOverrides($map);
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        $raw = $this->record->claimMapping ?? [];
        $cfg = $raw['_config'] ?? [];
        return is_array($cfg) ? $cfg : [];
    }

    private function configString(string $key): ?string
    {
        $v = $this->config()[$key] ?? null;
        return is_string($v) ? $v : null;
    }
}
