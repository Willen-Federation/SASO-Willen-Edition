<?php
namespace saso\settingAdmin;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

/**
 * Admin settings page for controlling which built-in item fields
 * are visible in the item registration / edit forms.
 *
 * Settings are persisted under key `item.fields.visibility` in
 * `system_setting` as a JSON map: {"price": true, "color": false, ...}.
 */
final class ItemFieldsView implements View
{
    use Setter;
    private \Closure $content;

    public bool $authorized    = false;
    public string $message     = '';
    public array $fieldVisible = [];

    /** Display labels for each toggleable built-in field (key → Japanese label). */
    public const FIELDS = [
        'price'   => '価格',
        'color'   => '色',
        'size'    => 'サイズ',
        'jan'     => 'JANコード',
        'isbn'    => 'ISBNコード',
        'packing' => '梱包',
        'note'    => '備考',
    ];

    public const SETTING_KEY = 'item.fields.visibility';

    public function __construct(private array $query, private array $post)
    {
        $pdo = DBConnection::getPdo();
        $this->authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );

        if (!$this->authorized) {
            return;
        }

        $encryptor      = AppKeyResolver::tryEncryptor()
            ?? new SecretEncryptor(str_repeat("\x00", 32));
        $settingService = new PdoSystemSettingService($pdo, $encryptor);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($this->post)) {
            $this->handlePost($settingService, $_SESSION['id'] ?? 'admin');
            $this->message = '商品入力項目の表示設定を保存しました。';
        }

        $this->loadSettings($settingService);
    }

    private function handlePost(PdoSystemSettingService $settingService, string $memberId): void
    {
        $visibility = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $visibility[$field] = isset($this->post['field_' . $field]);
        }
        $settingService->set(
            new SettingKey(self::SETTING_KEY),
            SettingValue::json($visibility),
            $memberId,
            'Updated item field visibility via admin UI',
        );
    }

    private function loadSettings(PdoSystemSettingService $settingService): void
    {
        $val = $settingService->get(new SettingKey(self::SETTING_KEY));
        $saved = [];
        if ($val !== null) {
            $decoded = $val->asJson();
            $saved   = is_array($decoded) ? $decoded : [];
        }
        foreach (array_keys(self::FIELDS) as $field) {
            $this->fieldVisible[$field] = (bool)($saved[$field] ?? true);
        }
    }

    public function display(): void
    {
        require_once 'settingAdmin/template/item-fields.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return '商品入力項目設定';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
