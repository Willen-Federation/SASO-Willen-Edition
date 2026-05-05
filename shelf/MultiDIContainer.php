<?php
namespace saso\shelf;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DBConnection;
use Saso\Domain\Setting\ShelfDimensionConfigLoader;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class MultiDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // Create ShelfDimensionConfigLoader from PDO connection
        try {
            $pdo = DBConnection::getPdo();
            $appKey = (string)(getenv('APP_KEY') ?: '');
            $encryptor = new SecretEncryptor(str_repeat("\x00", 32));
            if ($appKey !== '') {
                $rawKey = base64_decode($appKey, true);
                if ($rawKey !== false && strlen($rawKey) === 32) {
                    $encryptor = new SecretEncryptor($rawKey);
                }
            }
            $settingService = new PdoSystemSettingService($pdo, $encryptor);
            $configLoader = new ShelfDimensionConfigLoader($settingService);
        } catch (\Exception) {
            $configLoader = null;
        }

        $this->ctrl = new MultiController($query);
        $this->usecase = new MultiUsecase(
            new MultiPresenter(
                new ListView($inside),
                new common\RegisterFailView('shelf/start'),
            ),
            $configLoader
        );
    }
}
