<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Setting\SettingKey;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use saso\settingAdmin\ItemFieldsView as FieldSettings;

final class RegisterView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;

    /** @var array<string,bool>  key → visible (true = show, false = hide) */
    public array $fieldVisible = [];

    public function __construct()
    {
        $this->loadFieldVisibility();
    }

    private function loadFieldVisibility(): void
    {
        try {
            $pdo            = DBConnection::getPdo();
            $encryptor      = AppKeyResolver::tryEncryptor()
                ?? new SecretEncryptor(str_repeat("\x00", 32));
            $settingService = new PdoSystemSettingService($pdo, $encryptor);
            $val            = $settingService->get(new SettingKey(FieldSettings::SETTING_KEY));
            if ($val !== null) {
                $decoded = $val->asJson();
                if (is_array($decoded)) {
                    foreach (array_keys(FieldSettings::FIELDS) as $f) {
                        $this->fieldVisible[$f] = (bool)($decoded[$f] ?? true);
                    }
                    return;
                }
            }
        } catch (\Throwable) {
            // Fall through to defaults — e.g. DB not ready on fresh install.
        }
        foreach (array_keys(FieldSettings::FIELDS) as $f) {
            $this->fieldVisible[$f] = true;
        }
    }

    public function display(): void
    {
        require_once 'item/template/register.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
