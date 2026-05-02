<?php

namespace saso\auth;

use saso\framework\Controller;
use saso\framework\Usecase;

final class ProviderTestController implements Controller
{
    private ProviderSaveInput $data;

    public function __construct(array $post)
    {
        $template = trim((string) ($post['provider_template'] ?? ''));

        $issuerUrl = match ($template) {
            'auth0'    => ProviderSaveController::buildAuth0IssuerUrl((string) ($post['auth0_domain'] ?? '')),
            'cognito'  => ProviderSaveController::buildCognitoIssuerUrl(
                (string) ($post['cognito_region'] ?? ''),
                (string) ($post['cognito_user_pool_id'] ?? ''),
            ),
            'firebase' => ProviderSaveController::buildFirebaseIssuerUrl((string) ($post['firebase_project_id'] ?? '')),
            'oidc'     => ProviderSaveController::normalizeUrl((string) ($post['oidc_issuer_url'] ?? '')),
            'saml'     => ProviderSaveController::normalizeUrl((string) ($post['saml_metadata_url'] ?? '')),
            default    => '',
        };

        $this->data = new ProviderSaveInput(
            template: $template,
            providerName: '',
            type: ($template === 'saml' ? 'saml' : 'oidc'),
            issuerUrl: $issuerUrl,
            clientId: '',
            clientSecret: null,
            scopes: null,
        );
    }

    public function input(Usecase $usecase): void
    {
        $usecase->handle($this->data);
    }
}
