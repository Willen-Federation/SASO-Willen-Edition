<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Infrastructure\Auth\PhpSessionStore;

/**
 * Builds an `https://{tenant}/authorize?...&prompt=login` URL that the My
 * Page passkey card uses to bounce the user through Auth0 for passkey
 * enrollment.
 *
 * This is intentionally separate from {@see \Saso\Application\Auth\LoginOrchestrator}:
 * the orchestrator's `beginLogin()` is the IdP-neutral path used by the
 * sign-in page, and we do not want to slip an Auth0-specific `prompt`
 * hint into the generic `LoginContext`. Plumbing the SDK config from a
 * record once, inside this builder, keeps the special-case contained.
 *
 * The session keys set here (`auth.state`, `auth.purpose`, `auth.provider_id`,
 * `auth.return_to`) match the ones the `/auth/callback` handler in
 * `index.php` already reads — so the callback dispatch transparently
 * routes the passkey-enrollment return through the existing OIDC code
 * exchange path before redirecting the user back to My Page.
 */
final class PasskeyEnrollmentUrlBuilder
{
    public function __construct(
        private readonly AuthProviderRepository $providers,
        private readonly string $callbackUrl,
    ) {
    }

    public function build(
        AuthProviderId $auth0ProviderId,
        string $memberId,
        string $returnTo,
    ): string {
        $record = $this->providers->findById($auth0ProviderId);
        if ($record === null || $record->type !== AuthProviderType::Oidc) {
            throw new \RuntimeException(
                'PasskeyEnrollmentUrlBuilder cannot build URL for non-OIDC provider id '
                .$auth0ProviderId->value,
            );
        }

        $cfg    = is_array($record->claimMapping) ? ($record->claimMapping['_config'] ?? []) : [];
        $domain = is_array($cfg) && is_string($cfg['domain'] ?? null) ? trim((string) $cfg['domain']) : '';
        if ($domain === '' && is_string($record->issuerOrMetadataUrl)) {
            $host = parse_url($record->issuerOrMetadataUrl, PHP_URL_HOST);
            $domain = is_string($host) ? $host : '';
        }
        if ($domain === '' || $record->clientId === null || $record->clientSecret === null) {
            throw new \RuntimeException(
                'Auth0 provider record is missing the domain/client_id/client_secret required for passkey enrollment.',
            );
        }

        $sdk = new Auth0(new SdkConfiguration(
            domain:           $domain,
            clientId:         $record->clientId,
            clientSecret:     $record->clientSecret,
            redirectUri:      $this->callbackUrl,
            cookieSecret:     hash('sha256', 'saso:auth0:passkey:'.$record->clientId.':'.$record->id->value),
            sessionStorage:   new PhpSessionStore('auth0_s'),
            transientStorage: new PhpSessionStore('auth0_t'),
            scope:            ['openid', 'profile', 'email'],
            usePkce:          true,
        ));

        $state = bin2hex(random_bytes(16));

        $url = $sdk->login(
            redirectUrl: $this->callbackUrl,
            params: [
                'state'  => $state,
                // `prompt=login` forces Auth0 to show the login screen even
                // if the user has a fresh session; that is where the New
                // Universal Login passkey-enrollment widget surfaces.
                'prompt' => 'login',
            ],
        );

        $_SESSION['auth.state']            = $state;
        $_SESSION['auth.provider_id']      = $auth0ProviderId->value;
        $_SESSION['auth.purpose']          = 'passkey_enroll';
        $_SESSION['auth.passkey_member']   = $memberId;
        $_SESSION['auth.passkey_expires']  = time() + 1800;
        $_SESSION['auth.return_to']        = $returnTo;

        return $url;
    }
}
