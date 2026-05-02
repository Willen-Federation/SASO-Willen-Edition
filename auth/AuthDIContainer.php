<?php
namespace saso\auth;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;

final class AuthDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        if(empty($post)) {
            $pdo = \saso\repository\DBConnection::getPdo();
            $encryptor = self::buildEncryptor();
            $repo = new \Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository($pdo, $encryptor);

            $this->ctrl = new AuthController($query);
            $this->usecase = new AuthUsecase(
                $repo,
                new AuthPresenter(
                    new AuthView(),
                )
            );
        } else {
            $this->ctrl = new AuthController($query, new LoginController($post));
            $this->usecase = new LoginUsecase(
                new DbFinder(),
                new DbUpdater(),
                new LoginPresenter(
                    new LoginView(),
                )
            );
        }
    }

    private static function buildEncryptor(): \Saso\Infrastructure\Auth\Crypto\SecretEncryptor
    {
        $raw = getenv('APP_KEY') ?: '';
        $bytes = base64_decode($raw, strict: true);
        if ($bytes === false) {
            $bytes = hex2bin($raw) ?: '';
        }
        if (strlen($bytes) !== 32) {
            // This will likely cause errors downstream, but the repository
            // requires a valid encryptor. In production APP_KEY must be correct.
            throw new \RuntimeException('APP_KEY must be 32 bytes (base64 or hex).');
        }
        return new \Saso\Infrastructure\Auth\Crypto\SecretEncryptor($bytes);
    }
}
