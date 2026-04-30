<?php

namespace saso\auth;

use DateTimeImmutable;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use saso\framework\DTO;
use saso\framework\Presenter;
use saso\framework\Usecase;
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

        $secret = null;
        if ($data->clientSecret !== null && $this->encryptor !== null) {
            $secret = $data->clientSecret;
        }

        $record = new AuthProviderRecord(
            id: new AuthProviderId(0),
            name: $data->providerName,
            type: AuthProviderType::from($data->type),
            issuerOrMetadataUrl: $data->issuerUrl ?: null,
            clientId: $data->clientId ?: null,
            clientSecret: $secret,
            scopes: $data->scopes,
            claimMapping: null,
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
        return '';
    }
}
