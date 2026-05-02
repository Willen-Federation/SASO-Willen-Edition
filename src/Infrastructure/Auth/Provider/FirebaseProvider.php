<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use Saso\Domain\Auth\Exception\AuthFailedException;

/**
 * Firebase Authentication provider — server-side OIDC via Google's hosted
 * discovery doc.
 *
 * **Note:** Firebase's _client_ SDK validates ID tokens against
 * `https://securetoken.google.com/{project_id}`; that surface is for
 * Firebase-issued tokens (anonymous, phone, magic link, …). For interactive
 * sign-in the OAuth client lives in **Google Cloud Console → Credentials
 * → OAuth 2.0 Client IDs**, and the discovery URL is
 * `https://accounts.google.com/.well-known/openid-configuration`. Configure
 * the `auth_provider` row with that issuer and the `client_id` /
 * `client_secret` from GCP — not from the Firebase project config.
 *
 * Configuration extras (under `claim_mapping._config`):
 *   - `project_id` Firebase project, recorded for documentation
 *   - `hd`         optional Workspace domain pin (rejects non-`hd` accounts)
 */
final class FirebaseProvider extends BaseOidcProvider
{
    /**
     * @param array<string, mixed> $claims
     */
    protected function postValidate(array $claims): void
    {
        $hd = $this->configString('hd');
        if ($hd === null || $hd === '') {
            return;
        }
        $claimedHd = isset($claims['hd']) && is_string($claims['hd']) ? $claims['hd'] : '';
        if ($claimedHd !== $hd) {
            throw AuthFailedException::callbackInvalid(
                sprintf('Workspace domain mismatch: expected "%s", got "%s".', $hd, $claimedHd),
            );
        }
    }
}
