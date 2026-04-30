<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use Saso\Domain\Auth\LogoutContext;

/**
 * AWS Cognito provider.
 *
 * Configuration extras (under `claim_mapping._config`):
 *   - `region`           e.g. `ap-northeast-1`
 *   - `user_pool_id`     e.g. `ap-northeast-1_AbCd1`
 *   - `hosted_ui_domain` e.g. `acme.auth.ap-northeast-1.amazoncognito.com`
 *
 * Discovery URL is the standard
 * `https://cognito-idp.{region}.amazonaws.com/{user_pool_id}/.well-known/openid-configuration`,
 * which Cognito serves but does **not** advertise an `end_session_endpoint`
 * — we use the Hosted UI's `/logout` route instead.
 */
final class CognitoProvider extends BaseOidcProvider
{
    protected function buildLogoutUrl(LogoutContext $context): ?string
    {
        $domain = $this->configString('hosted_ui_domain');
        if ($domain === null || $domain === '') {
            return null;
        }
        $params = [
            'client_id'  => (string) $this->record->clientId,
            'logout_uri' => $context->returnTo,
        ];
        return sprintf('https://%s/logout?%s', $domain, http_build_query($params));
    }
}
