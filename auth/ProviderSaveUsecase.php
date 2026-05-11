<?php

namespace saso\auth;

use DateTimeImmutable;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use saso\framework\DTO;
use saso\framework\Presenter;
use saso\framework\Usecase;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use saso\util\monad\Either;

final class ProviderSaveUsecase implements Usecase
{
    private DTO $output;

    public function __construct(
        private AuthProviderRepository $repo,
        private ?SecretEncryptor $encryptor,
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        $error = $this->validate($data);
        if ($error !== '') {
            $this->output = new ProviderNewInput(errorMessage: $error);
            return;
        }

        // Surface a clear admin-facing error when the application has been
        // started without an APP_KEY: silently dropping the secret would
        // produce the "認証プロバイダーが正しく設定されていません" message
        // at the next sign-in attempt with no clue why.
        if ($data->clientSecret !== null && $data->clientSecret !== '' && $this->encryptor === null) {
            $this->output = new ProviderNewInput(
                errorMessage: 'クライアントシークレットを保存できません。サーバーの APP_KEY が未設定です。管理者にご確認ください。',
            );
            return;
        }

        $secret = ($data->clientSecret !== null && $data->clientSecret !== '')
            ? $data->clientSecret
            : null;

        $record = new AuthProviderRecord(
            id: new AuthProviderId(0),
            name: $data->providerName,
            type: AuthProviderType::from($data->type),
            issuerOrMetadataUrl: $data->issuerUrl !== '' ? $data->issuerUrl : null,
            clientId: $data->clientId !== '' ? $data->clientId : null,
            clientSecret: $secret,
            scopes: $data->scopes,
            // _config.flavor is what AuthProviderFactory::buildOidc() reads to
            // pick the concrete provider class (Auth0, Cognito, …). Without it
            // every wizard-saved row silently falls back to GenericOidcProvider
            // and login surfaces SASO-AUTH-1006.
            claimMapping: self::buildClaimMapping($data),
            // Validation above enforces the per-template required fields
            // (Auth0 requires clientSecret, etc.), so reaching this point
            // means the provider has enough config to attempt sign-in.
            // Admins can still toggle the row off via ./admin/auth/.
            enabled: true,
            isDefault: false,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
        );

        $this->repo->save($record);
        $this->output = new ProviderNewInput();
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }

    /**
     * Builds the `claim_mapping` JSON blob persisted alongside the row.
     *
     * `_config.flavor` is the discriminator AuthProviderFactory::buildOidc()
     * uses to pick the concrete provider class. Without it the wizard would
     * silently downgrade Auth0 → GenericOidcProvider and login would surface
     * SASO-AUTH-1006 even for syntactically-valid Auth0 settings. For Auth0
     * we also stash `_config.domain` so Auth0Provider can use the bare host
     * directly (it falls back to parse_url-on-the-issuer otherwise).
     *
     * @return array<string, array<string, string>>|null
     */
    private static function buildClaimMapping(DTO $data): ?array
    {
        $template = (string) $data->template;
        if ($template === '' || $template === 'oidc' || $template === 'saml') {
            return null;
        }
        $config = ['flavor' => $template];
        if ($template === 'auth0') {
            $issuerUrl = (string) $data->issuerUrl;
            if ($issuerUrl !== '') {
                $host = parse_url($issuerUrl, PHP_URL_HOST);
                if (is_string($host) && $host !== '') {
                    $config['domain'] = $host;
                }
            }
        }
        return ['_config' => $config];
    }

    private function validate(DTO $data): string
    {
        if ($data->providerName === '') {
            return 'プロバイダー名を入力してください。';
        }
        if (!in_array($data->template, ['auth0', 'cognito', 'firebase', 'oidc', 'saml'], true)) {
            return 'プロバイダーの種類を選択してください。';
        }
        if ($data->issuerUrl === '') {
            return match ($data->template) {
                'auth0'    => 'Auth0 ドメインを入力してください。',
                'cognito'  => 'リージョンとユーザープール ID を入力してください。',
                'firebase' => 'Firebase プロジェクト ID を入力してください。',
                'oidc'     => '発行者 URL を入力してください。',
                'saml'     => 'メタデータ URL を入力してください。',
                default    => 'URL を入力してください。',
            };
        }
        if (filter_var($data->issuerUrl, FILTER_VALIDATE_URL) === false) {
            return match ($data->template) {
                'auth0'    => 'Auth0 ドメインの形式が正しくありません。例: example.auth0.com',
                'cognito'  => 'リージョンまたはユーザープール ID の形式が正しくありません。',
                'firebase' => 'Firebase プロジェクト ID の形式が正しくありません。',
                'oidc'     => '発行者 URL の形式が正しくありません。https:// で始まる絶対 URL を入力してください。',
                'saml'     => 'メタデータ URL の形式が正しくありません。https:// で始まる絶対 URL を入力してください。',
                default    => 'URL の形式が正しくありません。',
            };
        }
        if (in_array($data->template, ['auth0', 'cognito', 'firebase', 'oidc'], true)) {
            $scheme = strtolower((string) parse_url($data->issuerUrl, PHP_URL_SCHEME));
            if ($scheme !== 'https') {
                return '発行者 URL は https:// で始まる必要があります。';
            }
            if ($data->clientId === '') {
                return 'クライアント ID を入力してください。';
            }
        }
        if ($data->template === 'auth0' && ($data->clientSecret === null || $data->clientSecret === '')) {
            return 'Auth0 のクライアントシークレットを入力してください。';
        }
        return '';
    }
}
