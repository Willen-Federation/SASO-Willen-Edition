<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\ProviderNewView;
use saso\auth\ProviderSaveController;
use saso\auth\ProviderSavePresenter;
use saso\auth\ProviderSaveUsecase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

/**
 * Regression coverage for the wizard's persistence of `_config.flavor`.
 *
 * Without this discriminator on the saved row, `AuthProviderFactory::buildOidc()`
 * silently downgrades Auth0 / Cognito / Firebase rows to GenericOidcProvider
 * and the user sees SASO-AUTH-1006 ("認証プロバイダーが正しく設定されていません") at
 * login time even though the wizard reported success.
 */
final class ProviderSaveUsecaseTest extends TestCase
{
    public function testAuth0SavePersistsFlavorAndDomain(): void
    {
        $record = $this->saveAndCapture([
            'provider_template' => 'auth0',
            'provider_name'     => 'Auth0 (test)',
            'auth0_domain'      => 'example.auth0.com',
            'client_id'         => 'abc123',
            'client_secret'     => 'shh',
        ]);

        self::assertSame(
            ['_config' => ['flavor' => 'auth0', 'domain' => 'example.auth0.com']],
            $record->claimMapping,
        );
        self::assertSame('https://example.auth0.com', $record->issuerOrMetadataUrl);
        self::assertSame('abc123', $record->clientId);
        self::assertSame('shh', $record->clientSecret);
        self::assertTrue($record->enabled);
    }

    public function testAuth0SavePreservesPathInDomainExtraction(): void
    {
        // Operators sometimes paste the full `https://tenant.auth0.com/` URL.
        // The controller normalises that to `https://tenant.auth0.com` and the
        // usecase should pull the host out for `_config.domain`.
        $record = $this->saveAndCapture([
            'provider_template' => 'auth0',
            'provider_name'     => 'Auth0',
            'auth0_domain'      => 'https://tenant.auth0.com/',
            'client_id'         => 'abc',
            'client_secret'     => 's',
        ]);
        self::assertIsArray($record->claimMapping);
        self::assertSame('tenant.auth0.com', $record->claimMapping['_config']['domain']);
    }

    public function testCognitoSavePersistsFlavorWithoutDomain(): void
    {
        $record = $this->saveAndCapture([
            'provider_template'      => 'cognito',
            'provider_name'          => 'Cognito',
            'cognito_region'         => 'ap-northeast-1',
            'cognito_user_pool_id'   => 'ap-northeast-1_XXXXXXXXX',
            'client_id'              => 'cog-id',
            'client_secret'          => 'cog-secret',
        ]);
        self::assertSame(['_config' => ['flavor' => 'cognito']], $record->claimMapping);
    }

    public function testFirebaseSavePersistsFlavorWithoutDomain(): void
    {
        $record = $this->saveAndCapture([
            'provider_template'   => 'firebase',
            'provider_name'       => 'Firebase',
            'firebase_project_id' => 'my-project-12345',
            'client_id'           => 'fb-id',
            'client_secret'       => 'fb-secret',
        ]);
        self::assertSame(['_config' => ['flavor' => 'firebase']], $record->claimMapping);
    }

    public function testGenericOidcLeavesClaimMappingNull(): void
    {
        // Generic OIDC has no flavor discriminator — that branch in the
        // factory IS the default, so we keep claim_mapping NULL to match
        // pre-existing rows from the legacy authExt flow.
        $record = $this->saveAndCapture([
            'provider_template' => 'oidc',
            'provider_name'     => 'Keycloak',
            'oidc_issuer_url'   => 'https://sso.example.com/realms/foo',
            'client_id'         => 'kc-id',
            'client_secret'     => 'kc-secret',
        ]);
        self::assertNull($record->claimMapping);
    }

    public function testSamlLeavesClaimMappingNull(): void
    {
        // SAML doesn't go through buildOidc(); the column-level `type` is
        // what dispatches it. No _config needed.
        $record = $this->saveAndCapture([
            'provider_template' => 'saml',
            'provider_name'     => 'AD FS',
            'saml_metadata_url' => 'https://idp.example.com/FederationMetadata.xml',
        ]);
        self::assertNull($record->claimMapping);
    }

    public function testValidationErrorDoesNotPersist(): void
    {
        $repo = $this->captureRepository();
        $usecase = new ProviderSaveUsecase(
            $repo,
            new SecretEncryptor(SecretEncryptor::generateKey()),
            new ProviderSavePresenter(new ProviderNewView()),
        );
        $controller = new ProviderSaveController([
            'provider_template' => 'auth0',
            'provider_name'     => '',  // missing
            'auth0_domain'      => 'example.auth0.com',
        ]);
        $controller->input($usecase);

        self::assertNull($repo->saved, 'Invalid input must not call save()');
    }

    public function testMissingAppKeyDoesNotFatal(): void
    {
        // Regression: when APP_KEY is unset, the DI container can't build a
        // SecretEncryptor and passes null repo + null encryptor. The wizard
        // used to TypeError at PdoAuthProviderRepository construction and
        // render a blank page; it must now surface a form-level error
        // instead so the operator knows what to fix.
        $usecase = new ProviderSaveUsecase(
            null,
            null,
            new ProviderSavePresenter(new ProviderNewView()),
        );
        $controller = new ProviderSaveController([
            'provider_template' => 'auth0',
            'provider_name'     => 'Auth0',
            'auth0_domain'      => 'example.auth0.com',
            'client_id'         => 'abc',
            'client_secret'     => 'shh',
        ]);
        $controller->input($usecase);

        $reflector = new \ReflectionClass($usecase);
        $outputProp = $reflector->getProperty('output');
        $outputProp->setAccessible(true);
        $output = $outputProp->getValue($usecase);

        self::assertStringContainsString('APP_KEY', $output->errorMessage);
    }

    /**
     * @param array<string, string> $post
     */
    private function saveAndCapture(array $post): AuthProviderRecord
    {
        $repo = new CapturingAuthProviderRepository();
        $usecase = new ProviderSaveUsecase(
            $repo,
            new SecretEncryptor(SecretEncryptor::generateKey()),
            new ProviderSavePresenter(new ProviderNewView()),
        );
        $controller = new ProviderSaveController($post);
        $controller->input($usecase);

        self::assertNotNull($repo->saved, 'Expected save() to be called for valid input');
        return $repo->saved;
    }

    private function captureRepository(): CapturingAuthProviderRepository
    {
        return new CapturingAuthProviderRepository();
    }
}

final class CapturingAuthProviderRepository implements AuthProviderRepository
{
    public ?AuthProviderRecord $saved = null;

    public function findById(AuthProviderId $id): ?AuthProviderRecord
    {
        return null;
    }

    public function listAll(): array
    {
        return [];
    }

    public function listEnabled(): array
    {
        return [];
    }

    public function save(AuthProviderRecord $record): AuthProviderRecord
    {
        $this->saved = $record;
        return $record;
    }

    public function delete(AuthProviderId $id): void
    {
    }
}
