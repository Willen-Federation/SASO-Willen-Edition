<?php
namespace saso\settingAdmin;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
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

        $appKey = (string)(getenv('APP_KEY') ?: '');
        $encryptor = new SecretEncryptor(str_repeat("\x00", 32)); // fallback no-op key
        if ($appKey !== '') {
            $rawKey = base64_decode($appKey, true);
            if ($rawKey !== false && strlen($rawKey) === 32) {
                $encryptor = new SecretEncryptor($rawKey);
            }
        }

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
        // Field type: 'int', 'string', or 'secret'. Secrets are encrypted at
        // rest by SettingValue::secret(). For the password we additionally
        // skip the update when the input was left blank so that the operator
        // can re-save other fields without re-typing the password every
        // time.
        $fields = [
            'default_locale'         => 'string',
            'mail.smtp_host'         => 'string',
            'mail.smtp_port'         => 'int',
            'mail.smtp_username'     => 'string',
            'mail.smtp_password'     => 'secret',
            'mail.smtp_encryption'   => 'string',
            'mail.smtp_auth'         => 'string',
            'mail.smtp_from_address' => 'string',
            'mail.smtp_from_name'    => 'string',
            'outputRow'              => 'int',
            'sheetAmount'            => 'int',
            'auth.mode'              => 'string',
        ];

        foreach ($fields as $keyStr => $type) {
            if (!isset($this->post[$keyStr])) {
                continue;
            }
            $val = (string) $this->post[$keyStr];
            // Don't overwrite an existing password when the field was left
            // blank — that's the standard "leave blank to keep current" UX.
            if ($type === 'secret' && $val === '') {
                continue;
            }
            $settingValue = match ($type) {
                'int'    => SettingValue::int((int) $val),
                'secret' => SettingValue::secret($val),
                default  => SettingValue::string($val),
            };
            $settingService->set(new SettingKey($keyStr), $settingValue, $memberId, 'Updated via Web UI');
        }
    }

    private function loadSettings(PdoSystemSettingService $settingService): void
    {
        // [type, default]. The 'secret' type is loaded for round-trip but its
        // plaintext is replaced with a masked indicator before being exposed
        // to the template — see below.
        $defaults = [
            'default_locale'         => ['string', 'en'],
            'mail.smtp_host'         => ['string', ''],
            'mail.smtp_port'         => ['int',    25],
            'mail.smtp_username'     => ['string', ''],
            'mail.smtp_password'     => ['secret', ''],
            'mail.smtp_encryption'   => ['string', 'none'],
            'mail.smtp_auth'         => ['string', 'none'],
            'mail.smtp_from_address' => ['string', ''],
            'mail.smtp_from_name'    => ['string', ''],
            'outputRow'              => ['int',    2],
            'sheetAmount'            => ['int',    10],
            'auth.mode'              => ['string', 'local'],
        ];

        foreach ($defaults as $keyStr => [$type, $default]) {
            $val = $settingService->get(new SettingKey($keyStr));
            if ($type === 'secret') {
                // Never echo the plaintext back to the form. The template
                // shows a "(unchanged — leave blank to keep)" placeholder
                // when this flag is true; submitting a non-empty value
                // overwrites it.
                $this->settings[$keyStr]            = '';
                $this->settings[$keyStr.'.is_set']  = $val !== null && $val->asString() !== '';
                continue;
            }
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
