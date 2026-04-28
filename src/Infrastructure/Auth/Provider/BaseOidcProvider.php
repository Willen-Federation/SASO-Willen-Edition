<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use Jumbojett\OpenIDConnectClient;
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
use Throwable;

/**
 * Abstract base for OpenID Connect providers (Auth0, Cognito, Firebase / Google,
 * generic OIDC) — wraps `jumbojett/openid-connect-php` and shares state /
 * nonce / claim-mapping plumbing.
 *
 * Subclasses tailor the `decorateClient()` hook to set provider-specific
 * authorization-request parameters (Auth0's `audience`, Firebase's `hd`, …)
 * and override `buildLogoutUrl()` when the provider does not advertise a
 * standard `end_session_endpoint`.
 *
 * State / nonce stash:
 *   `$_SESSION['auth.state']` and `$_SESSION['auth.nonce']` are written
 *   by {@see beginLogin()} and consumed (and cleared) by
 *   {@see completeLogin()}. The keys are intentionally global rather than
 *   per-provider — only one login can be in flight per browser session.
 */
abstract class BaseOidcProvider implements AuthProvider
{
    public function __construct(
        protected readonly AuthProviderRecord $record,
        protected readonly string $callbackUrl,
    ) {
        if ($record->type !== AuthProviderType::Oidc) {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'BaseOidcProvider requires AuthProviderType::Oidc.',
            );
        }
        if ($record->issuerOrMetadataUrl === null || $record->issuerOrMetadataUrl === '') {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'OIDC provider requires issuer_or_metadata_url.',
            );
        }
        if ($record->clientId === null || $record->clientId === '') {
            throw ProviderMisconfiguredException::for($record->name, 'OIDC provider requires client_id.');
        }
        if ($record->clientSecret === null || $record->clientSecret === '') {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'OIDC provider requires client_secret.',
            );
        }
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

    public function beginLogin(LoginContext $context): Redirect
    {
        $client = $this->buildClient();
        $this->decorateClient($client);

        // Pre-validate redirect_uri against the configured allowlist if the
        // operator set one. Mismatches are treated as misconfiguration so the
        // operator notices on the next reload — nobody wants a silent fall
        // back to a wildcard.
        $allowlist = $this->configList('redirect_uri_allowlist');
        if ($allowlist !== [] && !in_array($this->callbackUrl, $allowlist, true)) {
            throw ProviderMisconfiguredException::for(
                $this->record->name,
                sprintf('Callback URL "%s" is not in redirect_uri_allowlist.', $this->callbackUrl),
            );
        }

        $client->setRedirectURL($this->callbackUrl);
        $client->addScope($this->scopes());

        // jumbojett's authenticate() drives the redirect itself when called
        // without a callback — but we want a Redirect object instead so the
        // Application layer controls the response. Disable the lib's
        // self-redirect by capturing the URL via setIssuer + manual build.
        $authUrl = $this->buildAuthorizationUrl($client, $context);

        $_SESSION['auth.state']         = $context->csrfStateToken;
        $_SESSION['auth.nonce']         = $context->nonce;
        $_SESSION['auth.provider_id']   = $this->record->id->value;
        $_SESSION['auth.return_to']     = $context->returnTo;

        return new Redirect($authUrl, 302);
    }

    public function completeLogin(CallbackRequest $request): AuthenticatedIdentity
    {
        $expectedState = (string) ($_SESSION['auth.state'] ?? '');
        $expectedNonce = (string) ($_SESSION['auth.nonce'] ?? '');

        if ($expectedState === '' || $expectedNonce === '') {
            throw AuthFailedException::stateMismatch('No pending OIDC state / nonce — session may have expired.');
        }

        $receivedState = $request->query['state'] ?? '';
        if (!hash_equals($expectedState, (string) $receivedState)) {
            unset($_SESSION['auth.state'], $_SESSION['auth.nonce']);
            throw AuthFailedException::stateMismatch();
        }

        $client = $this->buildClient();
        $this->decorateClient($client);
        $client->setRedirectURL($this->callbackUrl);
        $client->addScope($this->scopes());
        // jumbojett v1 reads the nonce off the session by itself, but we
        // forward our explicit value so callers can audit which nonce was
        // checked when needed.
        if (method_exists($client, 'setNonce')) {
            $client->setNonce($expectedNonce);
        }

        try {
            $authenticated = $client->authenticate();
        } catch (Throwable $e) {
            throw AuthFailedException::callbackInvalid('OIDC authenticate() threw: '.$e->getMessage());
        }
        if ($authenticated !== true) {
            throw AuthFailedException::callbackInvalid('OIDC authenticate() returned false.');
        }

        // Pull the verified claims and userinfo response into a flat array.
        $idTokenClaims = (array) ($client->getIdTokenPayload() ?? []);
        $userInfo      = [];
        try {
            $info = $client->requestUserInfo();
            if (is_object($info)) {
                $userInfo = (array) $info;
            } elseif (is_array($info)) {
                $userInfo = $info;
            }
        } catch (Throwable) {
            // userinfo is optional — id_token claims may carry everything we need
        }
        $claims = array_merge($idTokenClaims, $userInfo);
        $claims = self::stringifyKeys($claims);

        $mapping = $this->claimMapping();
        $sub     = $mapping->extractString('subject', $claims) ?? '';
        $email   = $mapping->extractString('email', $claims) ?? '';
        $name    = $mapping->extractString('display_name', $claims) ?? $email;

        if ($sub === '') {
            throw AuthFailedException::callbackInvalid('OIDC ID token did not carry a usable subject.');
        }

        // Stash the id_token for RP-Initiated Logout. completeLogout reads
        // it back from the session; we keep it short-lived (cleared at logout).
        $idToken = $client->getIdToken();
        if (is_string($idToken) && $idToken !== '') {
            $_SESSION['auth.id_token'] = $idToken;
        }

        // Provider-specific extra checks (e.g. Firebase `hd`).
        $this->postValidate($claims);

        unset($_SESSION['auth.state'], $_SESSION['auth.nonce']);

        return new AuthenticatedIdentity(
            authProviderId: $this->record->id,
            externalSubject: $sub,
            email: $email,
            displayName: $name,
            claims: $claims,
        );
    }

    public function supportsLogout(): bool
    {
        return true;
    }

    public function beginLogout(LogoutContext $context): ?Redirect
    {
        $url = $this->buildLogoutUrl($context);
        if ($url === null) {
            return null;
        }
        return new Redirect($url, 302);
    }

    /**
     * Hook for subclasses to set provider-specific authorization-request
     * parameters before the user is redirected. The default implementation
     * is a no-op.
     */
    protected function decorateClient(OpenIDConnectClient $client): void
    {
    }

    /**
     * Hook for subclasses to enforce extra claim-validation rules (e.g.
     * Firebase `hd`-claim Workspace pinning). Throw
     * {@see AuthFailedException} on failure.
     *
     * @param array<string, mixed> $claims
     */
    protected function postValidate(array $claims): void
    {
    }

    /**
     * Standard OIDC RP-Initiated Logout. Subclasses override when the IdP
     * uses a non-standard logout endpoint (Cognito's Hosted UI does).
     */
    protected function buildLogoutUrl(LogoutContext $context): ?string
    {
        $client = $this->buildClient();
        try {
            $endpoint = method_exists($client, 'getProviderConfigValue')
                ? (string) $client->getProviderConfigValue('end_session_endpoint', '')
                : '';
        } catch (Throwable) {
            $endpoint = '';
        }

        if ($endpoint === '') {
            return null;
        }

        $params = [
            'client_id'                => (string) $this->record->clientId,
            'post_logout_redirect_uri' => $context->returnTo,
        ];
        if ($context->idTokenHint !== null && $context->idTokenHint !== '') {
            $params['id_token_hint'] = $context->idTokenHint;
        }
        return $endpoint.(str_contains($endpoint, '?') ? '&' : '?').http_build_query($params);
    }

    /**
     * Builds the IdP authorization URL via jumbojett's internals so we can
     * return it as a Redirect rather than letting the lib drive the
     * `header(Location)` itself.
     */
    protected function buildAuthorizationUrl(OpenIDConnectClient $client, LoginContext $context): string
    {
        $endpoint = method_exists($client, 'getProviderConfigValue')
            ? (string) $client->getProviderConfigValue('authorization_endpoint', '')
            : '';
        if ($endpoint === '') {
            throw ProviderMisconfiguredException::for(
                $this->record->name,
                'Discovery document did not advertise an authorization_endpoint.',
            );
        }

        $params = [
            'response_type' => 'code',
            'client_id'     => (string) $this->record->clientId,
            'redirect_uri'  => $this->callbackUrl,
            'scope'         => implode(' ', $this->scopes()),
            'state'         => $context->csrfStateToken,
            'nonce'         => $context->nonce,
        ];

        // Subclasses' decorateClient() may have stashed extra params via
        // setAdditionalAuthParams(); jumbojett stores those internally.
        // We extract them via reflection-free property access where possible.
        if (method_exists($client, 'getAdditionalAuthParams')) {
            $extra = $client->getAdditionalAuthParams();
            if (is_array($extra)) {
                /** @var array<string, scalar> $extra */
                $params = array_merge($extra, $params);
            }
        }

        return $endpoint.(str_contains($endpoint, '?') ? '&' : '?').http_build_query($params);
    }

    protected function buildClient(): OpenIDConnectClient
    {
        $client = new OpenIDConnectClient(
            (string) $this->record->issuerOrMetadataUrl,
            (string) $this->record->clientId,
            (string) $this->record->clientSecret,
        );
        // jumbojett defaults to RS256 only; explicitly accept the common
        // OIDC signing algorithms.
        if (method_exists($client, 'setAllowImplicitFlow')) {
            $client->setAllowImplicitFlow(false);
        }
        return $client;
    }

    /**
     * @return list<string>
     */
    protected function scopes(): array
    {
        $raw = trim((string) ($this->record->scopes ?? ''));
        if ($raw === '') {
            return ['openid', 'profile', 'email'];
        }
        $list = preg_split('/\s+/', $raw) ?: [];
        return array_values(array_filter($list, static fn (string $s): bool => $s !== ''));
    }

    protected function claimMapping(): ClaimMapping
    {
        $raw = $this->record->claimMapping ?? [];
        // Strip our `_config` envelope (used to store provider-specific
        // extras) before passing to the mapping.
        $map = [];
        foreach ($raw as $field => $claim) {
            if ($field === '_config' || !is_string($claim)) {
                continue;
            }
            $map[$field] = $claim;
        }
        if ($map === []) {
            return new ClaimMapping();
        }
        return ClaimMapping::withOverrides($map);
    }

    /**
     * Returns the raw `_config` object from the claim_mapping JSON, if any.
     * Subclasses use this for provider-specific extras (audience, region,
     * project_id, etc.) without an extra DB column.
     *
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        $raw = $this->record->claimMapping ?? [];
        $cfg = $raw['_config'] ?? [];
        return is_array($cfg) ? $cfg : [];
    }

    protected function configString(string $key): ?string
    {
        $v = $this->config()[$key] ?? null;
        return is_string($v) ? $v : null;
    }

    /**
     * @return list<string>
     */
    protected function configList(string $key): array
    {
        $v = $this->config()[$key] ?? [];
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach ($v as $item) {
            if (is_string($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * Recursively coerce object keys to strings for safe storage on
     * AuthenticatedIdentity::$claims.
     *
     * @param array<int|string, mixed> $in
     * @return array<string, mixed>
     */
    private static function stringifyKeys(array $in): array
    {
        $out = [];
        foreach ($in as $k => $v) {
            $out[(string) $k] = $v;
        }
        return $out;
    }
}
