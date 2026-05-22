<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

/**
 * Resolves Auth0 Management API credentials from environment variables.
 *
 * Returns `null` when the deployment has not configured M2M credentials —
 * in that case My Page renders the passkey card in a "list unavailable"
 * mode, but the registration redirect still works because it uses the
 * Auth0 SDK / Universal Login (no Management API needed).
 *
 * Env vars (see ADR-0019):
 *   - AUTH0_M2M_DOMAIN         tenant host, e.g. `acme.eu.auth0.com`
 *                              (falls back to the linked Auth0 provider's
 *                              `_config.domain` when this lookup is given
 *                              one)
 *   - AUTH0_M2M_CLIENT_ID      M2M application's client id
 *   - AUTH0_M2M_CLIENT_SECRET  M2M application's client secret
 */
final readonly class Auth0PasskeyConfig
{
    public function __construct(
        public string $domain,
        public string $clientId,
        public string $clientSecret,
    ) {
    }

    public static function fromEnv(?string $fallbackDomain = null): ?self
    {
        $domain       = trim((string) (getenv('AUTH0_M2M_DOMAIN') ?: ''));
        $clientId     = trim((string) (getenv('AUTH0_M2M_CLIENT_ID') ?: ''));
        $clientSecret = (string) (getenv('AUTH0_M2M_CLIENT_SECRET') ?: '');

        if ($domain === '' && $fallbackDomain !== null) {
            $domain = trim($fallbackDomain);
        }

        if ($domain === '' || $clientId === '' || $clientSecret === '') {
            return null;
        }

        return new self($domain, $clientId, $clientSecret);
    }
}
