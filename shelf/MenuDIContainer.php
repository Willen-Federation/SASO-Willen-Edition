<?php
namespace saso\shelf;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Setting\ShelfDimensionConfigLoader;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class MenuDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
    }
    public function flow(): View
    {
        $view = new MenuView();

        // Load and inject dimension metadata
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
            $dimensionConfig = $configLoader->load();
            $view->set('dimensionMetadata', $dimensionConfig->getEnabledDimensions());
        } catch (\Exception) {
            // Fall back to no metadata
        }

        return $view;
    }
}
