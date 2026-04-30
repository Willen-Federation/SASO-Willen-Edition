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
            'auth0'    => 'https://' . trim((string) ($post['auth0_domain'] ?? ''), '/'),
            'cognito'  => sprintf(
                'https://cognito-idp.%s.amazonaws.com/%s',
                trim((string) ($post['cognito_region'] ?? '')),
                trim((string) ($post['cognito_user_pool_id'] ?? '')),
            ),
            'firebase' => 'https://securetoken.google.com/' . trim((string) ($post['firebase_project_id'] ?? '')),
            'oidc'     => trim((string) ($post['oidc_issuer_url'] ?? '')),
            'saml'     => trim((string) ($post['saml_metadata_url'] ?? '')),
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
}
