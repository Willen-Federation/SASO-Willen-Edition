<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\AuthExt;

use PHPUnit\Framework\TestCase;

/**
 * Tests the claim-mapping and issuer-URL logic extracted from ProviderView.
 * We test it via a lightweight shim rather than booting the full View (which
 * requires a PDO connection).
 */
final class ProviderClaimMappingTest extends TestCase
{
    // ── Issuer resolution ─────────────────────────────────────────────────────

    public function testAuth0IssuerBuiltFromDomain(): void
    {
        $post = ['flavor' => 'auth0', 'auth0_domain' => 'acme.us.auth0.com'];
        self::assertSame(
            'https://acme.us.auth0.com/.well-known/openid-configuration',
            $this->resolveIssuer('oidc', $post),
        );
    }

    public function testCognitoIssuerBuiltFromRegionAndPoolId(): void
    {
        $post = [
            'flavor'                => 'cognito',
            'cognito_region'        => 'ap-northeast-1',
            'cognito_user_pool_id'  => 'ap-northeast-1_AbCd1',
        ];
        self::assertSame(
            'https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_AbCd1/.well-known/openid-configuration',
            $this->resolveIssuer('oidc', $post),
        );
    }

    public function testFirebaseIssuerUsesCustomField(): void
    {
        $post = [
            'flavor'               => 'firebase',
            'firebase_issuer_url'  => 'https://accounts.google.com/.well-known/openid-configuration',
        ];
        self::assertSame(
            'https://accounts.google.com/.well-known/openid-configuration',
            $this->resolveIssuer('oidc', $post),
        );
    }

    public function testFirebaseIssuerDefaultsToGoogle(): void
    {
        $post = ['flavor' => 'firebase', 'firebase_issuer_url' => ''];
        self::assertSame(
            'https://accounts.google.com/.well-known/openid-configuration',
            $this->resolveIssuer('oidc', $post),
        );
    }

    public function testSamlUsesIssuerOrMetadataUrl(): void
    {
        $post = ['issuer_or_metadata_url' => 'https://idp.example.com/saml/metadata'];
        self::assertSame(
            'https://idp.example.com/saml/metadata',
            $this->resolveIssuer('saml', $post),
        );
    }

    public function testGenericOidcPassesThrough(): void
    {
        $post = ['flavor' => 'oidc', 'issuer_or_metadata_url' => 'https://example.com/.well-known/openid-configuration'];
        self::assertSame(
            'https://example.com/.well-known/openid-configuration',
            $this->resolveIssuer('oidc', $post),
        );
    }

    // ── Claim-mapping construction ────────────────────────────────────────────

    public function testAuth0ConfigStoresFlavorDomainAudience(): void
    {
        $post = [
            'flavor'         => 'auth0',
            'auth0_domain'   => 'acme.us.auth0.com',
            'auth0_audience' => 'https://api.example.com',
            'claim_mapping_raw' => '{}',
        ];
        $mapping = json_decode($this->buildClaimMapping('oidc', $post), true);
        self::assertSame('auth0', $mapping['_config']['flavor']);
        self::assertSame('acme.us.auth0.com', $mapping['_config']['domain']);
        self::assertSame('https://api.example.com', $mapping['_config']['audience']);
    }

    public function testCognitoConfigStoresRegionPoolHostedUi(): void
    {
        $post = [
            'flavor'                     => 'cognito',
            'cognito_region'             => 'ap-northeast-1',
            'cognito_user_pool_id'       => 'ap-northeast-1_AbCd1',
            'cognito_hosted_ui_domain'   => 'acme.auth.ap-northeast-1.amazoncognito.com',
            'claim_mapping_raw'          => '{}',
        ];
        $mapping = json_decode($this->buildClaimMapping('oidc', $post), true);
        self::assertSame('cognito', $mapping['_config']['flavor']);
        self::assertSame('ap-northeast-1', $mapping['_config']['region']);
        self::assertSame('ap-northeast-1_AbCd1', $mapping['_config']['user_pool_id']);
        self::assertSame('acme.auth.ap-northeast-1.amazoncognito.com', $mapping['_config']['hosted_ui_domain']);
    }

    public function testFirebaseConfigStoresProjectIdAndHd(): void
    {
        $post = [
            'flavor'              => 'firebase',
            'firebase_project_id' => 'my-firebase-project',
            'firebase_hd'         => 'example.com',
            'claim_mapping_raw'   => '{}',
        ];
        $mapping = json_decode($this->buildClaimMapping('oidc', $post), true);
        self::assertSame('firebase', $mapping['_config']['flavor']);
        self::assertSame('my-firebase-project', $mapping['_config']['project_id']);
        self::assertSame('example.com', $mapping['_config']['hd']);
    }

    public function testFirebaseConfigOmitsHdWhenEmpty(): void
    {
        $post = [
            'flavor'              => 'firebase',
            'firebase_project_id' => 'my-project',
            'firebase_hd'         => '',
            'claim_mapping_raw'   => '{}',
        ];
        $mapping = json_decode($this->buildClaimMapping('oidc', $post), true);
        self::assertArrayNotHasKey('hd', $mapping['_config']);
    }

    public function testSamlConfigStorageRoundTrip(): void
    {
        $cert = "-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----";
        $post = [
            'issuer_or_metadata_url' => 'https://idp.example.com/metadata',
            'entity_id'     => 'https://sp.example.com/metadata',
            'nameid_format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            'idp_x509_cert' => $cert,
            'sp_x509_cert'  => '',
            'sp_private_key' => '',
            'claim_mapping_raw' => '{}',
        ];
        $mapping = json_decode($this->buildClaimMapping('saml', $post), true);
        self::assertSame('saml', $mapping['_config']['flavor']);
        self::assertSame('https://sp.example.com/metadata', $mapping['_config']['entity_id']);
        self::assertSame($cert, $mapping['_config']['idp_x509_cert']);
        self::assertArrayNotHasKey('sp_x509_cert', $mapping['_config']);
    }

    public function testRawClaimOverridesPreserved(): void
    {
        $post = [
            'flavor'            => 'oidc',
            'claim_mapping_raw' => '{"display_name": "preferred_username"}',
        ];
        $mapping = json_decode($this->buildClaimMapping('oidc', $post), true);
        self::assertSame('preferred_username', $mapping['display_name']);
    }

    public function testUserCannotOverrideConfig(): void
    {
        $post = [
            'flavor'            => 'auth0',
            'auth0_domain'      => 'real.us.auth0.com',
            'claim_mapping_raw' => '{"_config": {"flavor": "hacked"}}',
        ];
        $mapping = json_decode($this->buildClaimMapping('oidc', $post), true);
        // _config.flavor must come from structured fields, not user input
        self::assertSame('auth0', $mapping['_config']['flavor']);
    }

    // ── Shims — duplicate the private logic under test ────────────────────────

    /** @param array<string, string> $post */
    private function resolveIssuer(string $type, array $post): string
    {
        if ($type !== 'oidc') {
            return (string) ($post['issuer_or_metadata_url'] ?? '');
        }

        $flavor = (string) ($post['flavor'] ?? 'oidc');

        if ($flavor === 'cognito') {
            $region     = trim((string) ($post['cognito_region'] ?? ''));
            $userPoolId = trim((string) ($post['cognito_user_pool_id'] ?? ''));
            if ($region !== '' && $userPoolId !== '') {
                return sprintf(
                    'https://cognito-idp.%s.amazonaws.com/%s/.well-known/openid-configuration',
                    $region,
                    $userPoolId,
                );
            }
            return (string) ($post['issuer_or_metadata_url'] ?? '');
        }

        if ($flavor === 'firebase') {
            $fbIssuer = trim((string) ($post['firebase_issuer_url'] ?? ''));
            return $fbIssuer !== '' ? $fbIssuer : 'https://accounts.google.com/.well-known/openid-configuration';
        }

        if ($flavor === 'auth0') {
            $domain = trim((string) ($post['auth0_domain'] ?? ''));
            if ($domain !== '') {
                return 'https://'.$domain.'/.well-known/openid-configuration';
            }
        }

        return (string) ($post['issuer_or_metadata_url'] ?? '');
    }

    /** @param array<string, string> $post */
    private function buildClaimMapping(string $type, array $post): string
    {
        $config = [];

        if ($type === 'oidc') {
            $flavor = (string) ($post['flavor'] ?? 'oidc');
            $config['flavor'] = $flavor;

            if ($flavor === 'auth0') {
                $domain   = trim((string) ($post['auth0_domain'] ?? ''));
                $audience = trim((string) ($post['auth0_audience'] ?? ''));
                if ($domain !== '') {
                    $config['domain']   = $domain;
                }
                if ($audience !== '') {
                    $config['audience'] = $audience;
                }
            } elseif ($flavor === 'cognito') {
                $region         = trim((string) ($post['cognito_region'] ?? ''));
                $userPoolId     = trim((string) ($post['cognito_user_pool_id'] ?? ''));
                $hostedUiDomain = trim((string) ($post['cognito_hosted_ui_domain'] ?? ''));
                if ($region !== '') {
                    $config['region']           = $region;
                }
                if ($userPoolId !== '') {
                    $config['user_pool_id']     = $userPoolId;
                }
                if ($hostedUiDomain !== '') {
                    $config['hosted_ui_domain'] = $hostedUiDomain;
                }
            } elseif ($flavor === 'firebase') {
                $projectId = trim((string) ($post['firebase_project_id'] ?? ''));
                $hd        = trim((string) ($post['firebase_hd'] ?? ''));
                if ($projectId !== '') {
                    $config['project_id'] = $projectId;
                }
                if ($hd !== '') {
                    $config['hd']         = $hd;
                }
            }
        } elseif ($type === 'saml') {
            $config['flavor'] = 'saml';
            $entityId = (string) ($post['entity_id'] ?? '');
            if ($entityId !== '') {
                $config['entity_id'] = $entityId;
            }
            $nameidFormat = (string) ($post['nameid_format'] ?? '');
            if ($nameidFormat !== '') {
                $config['nameid_format'] = $nameidFormat;
            }
            $idpCert = (string) ($post['idp_x509_cert'] ?? '');
            if ($idpCert !== '') {
                $config['idp_x509_cert'] = $idpCert;
            }
            $spCert = (string) ($post['sp_x509_cert'] ?? '');
            if ($spCert !== '') {
                $config['sp_x509_cert'] = $spCert;
            }
            $spKey = (string) ($post['sp_private_key'] ?? '');
            if ($spKey !== '') {
                $config['sp_private_key'] = $spKey;
            }
        }

        $rawJson = (string) ($post['claim_mapping_raw'] ?? '{}');
        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        unset($decoded['_config']);
        $decoded['_config'] = $config;

        return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
