<?php

namespace saso\auth;

use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;
use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DBConnection;

final class ProviderNewDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        // AJAX test-connection should not be wrapped in the application layout.
        return ($_GET['action'] ?? '') === 'test';
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // AJAX probe used by the wizard's "接続をテスト" button. Reuses the
        // same builder methods the save flow does, so the URL we probe is
        // the URL we would persist.
        if (($_GET['action'] ?? '') === 'test'
            && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            
            $token = (string) ($post['csrftoken'] ?? '');
            if (!\saso\util\CSRFtoken::verify($token)) {
                $this->ctrl = new \saso\common\EmptyController();
                $this->usecase = new \saso\common\EmptyUsecase(
                    new \saso\common\FailJsonView(errorMessage: 'CSRF token invalid.')
                );
                return;
            }

            $this->ctrl = new ProviderTestController($post);
            $this->usecase = new ProviderTestUsecase(
                new ProviderTestPresenter(
                    new ProviderTestView()
                )
            );
            return;
        }

        if (empty($post)) {
            $this->ctrl    = new ProviderNewController($query);
            $this->usecase = new EmptyUsecase(
                new ProviderNewPresenter(
                    new ProviderNewView(),
                ),
            );
        } else {
            $pdo       = DBConnection::getPdo();
            $encryptor = self::buildEncryptor();
            // PdoAuthProviderRepository requires a non-null SecretEncryptor.
            // When APP_KEY is missing we cannot build one; pass a null repo so
            // ProviderSaveUsecase can surface the misconfiguration as a form
            // error instead of fatally TypeError-ing at construction.
            $repo      = $encryptor === null
                ? null
                : new PdoAuthProviderRepository($pdo, $encryptor);

            $this->ctrl    = new ProviderSaveController($post);
            $this->usecase = new ProviderSaveUsecase(
                $repo,
                $encryptor,
                new ProviderSavePresenter(
                    new ProviderNewView(),
                ),
            );
        }
    }

    private static function buildEncryptor(): ?SecretEncryptor
    {
        return AppKeyResolver::tryEncryptor();
    }
}
