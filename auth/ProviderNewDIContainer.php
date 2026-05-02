<?php

namespace saso\auth;

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
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
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
            $repo      = new PdoAuthProviderRepository($pdo, $encryptor);

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
        $raw = getenv('APP_KEY');
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $bytes = base64_decode($raw, strict: true);
        if ($bytes === false) {
            $bytes = hex2bin($raw) ?: null;
        }
        if ($bytes === null || strlen($bytes) !== 32) {
            return null;
        }
        return new SecretEncryptor($bytes);
    }
}
