<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use Jumbojett\OpenIDConnectClient;
use Saso\Domain\Auth\LogoutContext;

/**
 * Auth0 provider.
 *
 * Configuration extras (under `claim_mapping._config`):
 *   - `domain`    Auth0 tenant (e.g. `acme.eu.auth0.com`) — also typically the
 *                 host part of `issuer_or_metadata_url`. Used to build the
 *                 `/v2/logout` URL.
 *   - `audience`  optional API audience to obtain Auth0 access tokens for.
 *
 * Auth0 implements RFC 7009 token revocation but **not** RP-Initiated Logout
 * exactly to spec — we use Auth0's `/v2/logout?client_id=…&returnTo=…`
 * instead.
 */
final class Auth0Provider extends BaseOidcProvider
{
    protected function decorateClient(OpenIDConnectClient $client): void
    {
        $audience = $this->configString('audience');
        if ($audience !== null && $audience !== '' && method_exists($client, 'setAdditionalAuthParams')) {
            $client->setAdditionalAuthParams(['audience' => $audience]);
        }
    }

    protected function buildLogoutUrl(LogoutContext $context): ?string
    {
        $domain = $this->configString('domain');
        if ($domain === null || $domain === '') {
            // Fall back to standard endpoint discovery via the parent.
            return parent::buildLogoutUrl($context);
        }
        $params = [
            'client_id' => (string) $this->record->clientId,
            'returnTo'  => $context->returnTo,
        ];
        return sprintf('https://%s/v2/logout?%s', $domain, http_build_query($params));
    }
}
