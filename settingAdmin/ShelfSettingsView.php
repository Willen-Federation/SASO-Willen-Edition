<?php
namespace saso\settingAdmin;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Setting\ShelfDimensionConfigLoader;
use Saso\Domain\Setting\ShelfDimensionMetadata;
use Saso\Domain\Setting\ShelfDimensionConfig;
use Saso\Domain\Setting\ShelfDimensionType;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class ShelfSettingsView implements View
{
    use Setter;
    private \Closure $content;
    public bool $authorized = false;
    public string $message = '';
    public array $dimensions = [];

    public function __construct(private array $post)
    {
        $pdo = DBConnection::getPdo();
        $this->authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );

        if (!$this->authorized) {
            return;
        }

        // Load current dimension configuration
        $settingService = null;
        try {
            $encryptor      = AppKeyResolver::tryEncryptor()
                ?? new SecretEncryptor(str_repeat("\x00", 32));
            $settingService = new PdoSystemSettingService($pdo, $encryptor);
            $configLoader = new ShelfDimensionConfigLoader($settingService);
            $config = $configLoader->load();

            $this->dimensions = array_map(
                fn($dim) => [
                    'position' => $dim->position,
                    'name' => $dim->name,
                    'description' => $dim->description,
                    'type' => $dim->type->value,
                    'enabled' => $dim->enabled,
                ],
                $config->dimensions
            );
        } catch (\Exception) {
            // Fall back to defaults
            for ($i = 1; $i <= 5; $i++) {
                $this->dimensions[] = [
                    'position' => $i,
                    'name' => '',
                    'description' => '',
                    'type' => 'numeric',
                    'enabled' => $i <= 4,
                ];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($this->post['save_shelf_config'])) {
            if ($settingService !== null) {
                $this->handlePost($settingService, $_SESSION['id'] ?? 'admin');
            } else {
                $this->message = 'Configuration service unavailable. Please try again.';
            }
        }
    }

    private function handlePost(PdoSystemSettingService $settingService, string $memberId): void
    {
        try {
            $dimensions = [];
            for ($i = 1; $i <= 10; $i++) {
                if (isset($this->post["dimension_{$i}_enabled"])) {
                    $dimensions[] = new ShelfDimensionMetadata(
                        name: $this->post["dimension_{$i}_name"] ?? '',
                        description: $this->post["dimension_{$i}_description"] ?? '',
                        type: ShelfDimensionType::from($this->post["dimension_{$i}_type"] ?? 'numeric'),
                        position: $i,
                        enabled: true,
                    );
                }
            }

            if (empty($dimensions)) {
                $this->message = 'At least one dimension must be enabled.';
                return;
            }

            $config = new ShelfDimensionConfig($dimensions);
            $settingKey = new \Saso\Domain\Setting\SettingKey('shelf.dimension.config');
            $settingValue = \Saso\Domain\Setting\SettingValue::json($config->toJson());

            $settingService->set(
                $settingKey,
                $settingValue,
                $memberId,
                'Shelf dimension configuration updated'
            );

            $this->message = 'Shelf dimension configuration saved successfully.';

            // Reload dimensions
            $configLoader = new ShelfDimensionConfigLoader($settingService);
            $reloadedConfig = $configLoader->load();

            $this->dimensions = array_map(
                fn($dim) => [
                    'position' => $dim->position,
                    'name' => $dim->name,
                    'description' => $dim->description,
                    'type' => $dim->type->value,
                    'enabled' => $dim->enabled,
                ],
                $reloadedConfig->dimensions
            );
        } catch (\Exception $e) {
            $this->message = 'Error saving configuration: ' . $e->getMessage();
        }
    }

    public function display(): void
    {
        require_once 'settingAdmin/template/shelf-settings.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return 'Shelf Settings';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
