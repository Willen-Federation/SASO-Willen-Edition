<?php

namespace saso\auth;

use saso\framework\Controller;
use saso\framework\Usecase;

final class ProviderSaveController implements Controller
{
    private ProviderSaveInput $data;

    public function __construct(array $post)
    {
        $template     = trim((string) ($post['provider_template'] ?? ''));
        $providerName = trim((string) ($post['provider_name'] ?? ''));
        $clientId     = trim((string) ($post['client_id'] ?? ''));
        $clientSecret = trim((string) ($post['client_secret'] ?? ''));
        $scopes       = trim((string) ($post['scopes'] ?? ''));

        $issuerUrl = match ($template) {
            'auth0'    => self::buildAuth0IssuerUrl((string) ($post['auth0_domain'] ?? '')),
            'cognito'  => self::buildCognitoIssuerUrl(
                (string) ($post['cognito_region'] ?? ''),
                (string) ($post['cognito_user_pool_id'] ?? ''),
            ),
            'firebase' => self::buildFirebaseIssuerUrl((string) ($post['firebase_project_id'] ?? '')),
            'oidc'     => self::normalizeUrl((string) ($post['oidc_issuer_url'] ?? '')),
            'saml'     => self::normalizeUrl((string) ($post['saml_metadata_url'] ?? '')),
            default    => '',
        };

        $type = ($template === 'saml') ? 'saml' : 'oidc';

        $this->data = new ProviderSaveInput(
            template: $template,
            providerName: $providerName,
            type: $type,
            issuerUrl: $issuerUrl,
            clientId: $clientId,
            clientSecret: $clientSecret ?: null,
            scopes: $scopes ?: null,
        );
    }

    public function input(Usecase $usecase): void
    {
        $usecase->handle($this->data);
    }

    /**
     * Normalises an Auth0 tenant identifier into a fully-qualified HTTPS issuer URL.
     *
     * Accepts any of the following operator inputs without producing the
     * "https://https://" double-prefix bug that the previous implementation
     * exhibited when the field was pasted with a protocol prefix:
     *
     *  - example.auth0.com
     *  - https://example.auth0.com
     *  - https://example.auth0.com/   (trailing slash trimmed)
     *  - http://example.auth0.com     (downgraded to https for safety)
     *  - example.auth0.com/realms/foo (path segments preserved without slashes)
     *
     * A blank or whitespace-only input returns an empty string so the
     * usecase-layer validator can surface a localised error.
     */
    public static function buildAuth0IssuerUrl(string $rawDomain): string
    {
        $domain = trim($rawDomain);
        if ($domain === '') {
            return '';
        }
        // Strip any user-provided scheme (with or without //) so we never
        // double-up the prefix below.
        $domain = (string) preg_replace('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', '', $domain);
        // Trim incidental whitespace and trailing slashes that operators
        // leave behind when copying from Auth0's dashboard.
        $domain = trim($domain);
        $domain = rtrim($domain, "/ \t\n\r\0\x0B");
        if ($domain === '') {
            return '';
        }
        return 'https://'.$domain;
    }

    public static function buildCognitoIssuerUrl(string $region, string $userPoolId): string
    {
        $region     = trim($region);
        $userPoolId = trim($userPoolId);
        if ($region === '' || $userPoolId === '') {
            return '';
        }
        return sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s',
            $region,
            $userPoolId,
        );
    }

    public static function buildFirebaseIssuerUrl(string $projectId): string
    {
        $projectId = trim($projectId);
        if ($projectId === '') {
            return '';
        }
        return 'https://securetoken.google.com/'.$projectId;
    }

    /**
     * Trims and validates a free-form URL field. Returns the original string
     * (trimmed) so the usecase validator can decide between "missing" and
     * "malformed" for each provider flavour.
     */
    public static function normalizeUrl(string $raw): string
    {
        return trim($raw);
    }
}
