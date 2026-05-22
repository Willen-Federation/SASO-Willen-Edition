<?php
namespace saso\settingAdmin;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class StartView implements View
{
    use Setter;
    private \Closure $content;
    public bool $authorized = false;
    public string $message = '';

    public array $settings = [];
    public array $envOverrides = [];

    public function __construct(private array $query, private array $post)
    {
        $pdo = DBConnection::getPdo();
        $this->authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );

        if (!$this->authorized) {
            return;
        }

        // The settings handled on this page are all non-secret, but the
        // PdoSystemSettingService still requires a working encryptor for
        // secret-type rows. Build one from APP_KEY using the shared resolver;
        // if APP_KEY is missing, fall back to a sentinel-only service that
        // raises the moment any caller actually touches a secret value.
        $encryptor      = AppKeyResolver::tryEncryptor()
            ?? new SecretEncryptor(str_repeat("\x00", 32));
        $settingService = new PdoSystemSettingService($pdo, $encryptor);

        $this->envOverrides = [
            'APP_HTTPS' => getenv('APP_HTTPS') !== false,
            'SAFE_MODE' => getenv('SAFE_MODE') !== false,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($settingService, $_SESSION['id'] ?? 'admin');
            $this->message = 'Settings saved successfully.';
        }

        $this->loadSettings($settingService);
    }

    private function handlePost(PdoSystemSettingService $settingService, string $memberId): void
    {
        $fields = [
            'default_locale' => 'string',
            'mail.smtp_host' => 'string',
            'mail.smtp_port' => 'int',
            'outputRow'      => 'int',
            'sheetAmount'    => 'int',
            'auth.mode'      => 'string',
        ];

        foreach ($fields as $keyStr => $type) {
            if (isset($this->post[$keyStr])) {
                $val = $this->post[$keyStr];
                $settingValue = $type === 'int'
                    ? SettingValue::int((int) $val)
                    : SettingValue::string((string) $val);
                $settingService->set(new SettingKey($keyStr), $settingValue, $memberId, 'Updated via Web UI');
            }
        }
    }

    private function loadSettings(PdoSystemSettingService $settingService): void
    {
        $defaults = [
            'default_locale' => ['string', 'en'],
            'mail.smtp_host' => ['string', ''],
            'mail.smtp_port' => ['int',    25],
            'outputRow'      => ['int',    2],
            'sheetAmount'    => ['int',    10],
            'auth.mode'      => ['string', 'local'],
        ];

        foreach ($defaults as $keyStr => [$type, $default]) {
            $val = $settingService->get(new SettingKey($keyStr));
            $this->settings[$keyStr] = $val !== null
                ? ($type === 'int' ? $val->asInt() : $val->asString())
                : $default;
        }
    }

    public function display(): void
    {
        require_once 'settingAdmin/template/start.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return __('ui.settings.title', [], null, 'System Settings');
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
