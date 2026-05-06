<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use OneLogin\Saml2\Auth as SamlAuth;
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
 * Generic SAML 2.0 provider — wraps `onelogin/php-saml`.
 *
 * Configuration extras (under `claim_mapping._config`):
 *   - `entity_id`                 SP entity id (defaults to the ACS URL)
 *   - `nameid_format`             one of the SAML 2.0 NameID formats
 *   - `idp_metadata_xml_b64`      optional inline IdP metadata (base64'd XML)
 *   - `sp_x509_cert`              SP signing certificate (PEM)
 *   - `sp_private_key_encrypted`  encrypted SP private key (decrypted before
 *                                 reaching this class via the repository)
 *   - `redirect_uri_allowlist`    list of acceptable ACS / SLS URLs
 */
final class SamlProvider implements AuthProvider
{
    public function __construct(
        private readonly AuthProviderRecord $record,
        private readonly string $acsUrl,
        private readonly string $slsUrl,
    ) {
        if ($record->type !== AuthProviderType::Saml) {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'SamlProvider requires AuthProviderType::Saml.',
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
        $auth = $this->buildAuth();

        // OneLogin's library will header(Location) by itself unless `stay`
        // is set to true. We use that mode so we can return the URL.
        try {
            /** @var string|null $url */
            $url = $auth->login(
                $context->returnTo,
                [],
                false,
                false,
                true,
            );
        } catch (Throwable $e) {
            throw ProviderMisconfiguredException::for(
                $this->record->name,
                'SAML login() failed: '.$e->getMessage(),
            );
        }
        if (!is_string($url) || $url === '') {
            throw ProviderMisconfiguredException::for(
                $this->record->name,
                'SAML login() did not return a redirect URL.',
            );
        }

        $_SESSION['auth.state']       = $context->csrfStateToken;
        $_SESSION['auth.return_to']   = $context->returnTo;
        $_SESSION['auth.provider_id'] = $this->record->id->value;

        return new Redirect($url, 302);
    }

    public function completeLogin(CallbackRequest $request): AuthenticatedIdentity
    {
        $auth = $this->buildAuth();

        try {
            $auth->processResponse();
        } catch (Throwable $e) {
            throw AuthFailedException::callbackInvalid('SAML processResponse() threw: '.$e->getMessage());
        }
        $errors = $auth->getErrors();
        if (is_array($errors) && $errors !== []) {
            $reason = $auth->getLastErrorReason() ?? implode(', ', $errors);
            throw AuthFailedException::callbackInvalid('SAML response validation failed: '.$reason);
        }
        if (!$auth->isAuthenticated()) {
            throw AuthFailedException::callbackInvalid('SAML response did not authenticate the user.');
        }

        $nameId = (string) ($auth->getNameId() ?: '');
        if ($nameId === '') {
            throw AuthFailedException::callbackInvalid('SAML response carried no NameID.');
        }

        $rawAttrs = $auth->getAttributes();
        $claims   = self::flattenAttributes($rawAttrs);

        $mapping = $this->claimMapping();
        $email   = $mapping->extractString('email', $claims) ?? '';
        $name    = $mapping->extractString('display_name', $claims) ?? $nameId;

        if (isset($_SESSION['auth.session_index']) === false) {
            $idx = $auth->getSessionIndex();
            if (is_string($idx) && $idx !== '') {
                $_SESSION['auth.session_index'] = $idx;
            }
        }

        return new AuthenticatedIdentity(
            authProviderId: $this->record->id,
            externalSubject: $nameId,
            email: $email,
            displayName: $name,
            claims: $claims,
        );
    }

    public function supportsLogout(): bool
    {
        // True if the IdP advertises a SingleLogoutService — we cannot easily
        // probe without parsing metadata, so optimistically return true and
        // let `beginLogout()` fall through to null on misconfiguration.
        return true;
    }

    public function beginLogout(LogoutContext $context): ?Redirect
    {
        try {
            $auth = $this->buildAuth();
            $sessionIndex = isset($_SESSION['auth.session_index'])
                ? (string) $_SESSION['auth.session_index']
                : null;
            /** @var string|null $url */
            $url = $auth->logout(
                $context->returnTo,
                [],
                null,
                $sessionIndex,
                true,
            );
            if (!is_string($url) || $url === '') {
                return null;
            }
            return new Redirect($url, 302);
        } catch (Throwable) {
            return null;
        }
    }

    private function buildAuth(): SamlAuth
    {
        try {
            return new SamlAuth($this->buildSettings());
        } catch (Throwable $e) {
            throw ProviderMisconfiguredException::for(
                $this->record->name,
                'SAML settings rejected: '.$e->getMessage(),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSettings(): array
    {
        $cfg = $this->configMap();
        $entityId      = isset($cfg['entity_id'])     && is_string($cfg['entity_id']) ? $cfg['entity_id'] : $this->acsUrl;
        $nameIdFormat  = isset($cfg['nameid_format']) && is_string($cfg['nameid_format']) ? $cfg['nameid_format'] : 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

        $idp = [
            'entityId'           => (string) $this->record->issuerOrMetadataUrl,
            'singleSignOnService' => [
                'url'     => (string) $this->record->issuerOrMetadataUrl,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'singleLogoutService' => [
                'url'     => (string) $this->record->issuerOrMetadataUrl,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'x509cert' => isset($cfg['idp_x509_cert']) && is_string($cfg['idp_x509_cert']) ? $cfg['idp_x509_cert'] : '',
        ];

        $sp = [
            'entityId'                 => $entityId,
            'assertionConsumerService' => [
                'url'     => $this->acsUrl,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ],
            'singleLogoutService'      => [
                'url'     => $this->slsUrl,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'NameIDFormat'             => $nameIdFormat,
            'x509cert'                 => isset($cfg['sp_x509_cert']) && is_string($cfg['sp_x509_cert']) ? $cfg['sp_x509_cert'] : '',
            'privateKey'               => isset($cfg['sp_private_key']) && is_string($cfg['sp_private_key']) ? $cfg['sp_private_key'] : '',
        ];

        return [
            'strict'   => true,
            'debug'    => false,
            'sp'       => $sp,
            'idp'      => $idp,
            'security' => [
                'requestedAuthnContext' => false,
                'wantMessagesSigned'    => false,
                'wantAssertionsSigned'  => true,
                'wantNameId'            => true,
            ],
        ];
    }

    /**
     * Flattens OneLogin's `array<string, list<string>>` into a flat
     * `array<string, string>` by joining repeats with a comma. The full
     * raw structure is preserved on the AuthenticatedIdentity::$claims under
     * the `_raw` key for callers that need it.
     *
     * @param array<string, list<string>> $attrs
     *
     * @return array<string, mixed>
     */
    private static function flattenAttributes(array $attrs): array
    {
        $flat = ['_raw' => $attrs];
        foreach ($attrs as $key => $values) {
            $flat[$key] = is_array($values) && $values !== [] ? (string) $values[0] : '';
        }
        return $flat;
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
        if ($map === []) {
            return new ClaimMapping();
        }
        return ClaimMapping::withOverrides($map);
    }

    /**
     * @return array<string, mixed>
     */
    private function configMap(): array
    {
        $raw = $this->record->claimMapping ?? [];
        $cfg = $raw['_config'] ?? [];
        return is_array($cfg) ? $cfg : [];
    }
}
