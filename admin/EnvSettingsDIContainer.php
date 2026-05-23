<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\EnvLoader;
use saso\util\EnvWriter;
use Saso\Application\Auth\AdminGuard;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class EnvSettingsDIContainer implements DIContainer
{
    private EnvSettingsView $view;

    private const ENV_PATH = __DIR__ . '/../.env';

    /**
     * Editable .env keys. Anything outside this list is left untouched
     * to avoid accidentally clobbering operator-specific values.
     */
    private const ENV_KEYS = [
        'DB_DSN',
        'DB_USER',
        'DB_PASSWORD',
        'APP_HTTPS',
        'APP_KEY',
        'JWT_SECRET',
        'WEBHOOK_SECRET',
        'APP_DOCUMENT_ROOT',
        'APP_PROGRAM_DIR',
        'AUTH0_M2M_DOMAIN',
        'AUTH0_M2M_CLIENT_ID',
        'AUTH0_M2M_CLIENT_SECRET',
        'SEED_ADMIN_ID',
        'SEED_ADMIN_PASSWORD',
    ];

    /** Secret keys must be masked when re-displayed. */
    private const ENV_SECRETS = [
        'DB_PASSWORD',
        'APP_KEY',
        'JWT_SECRET',
        'WEBHOOK_SECRET',
        'AUTH0_M2M_CLIENT_SECRET',
        'SEED_ADMIN_PASSWORD',
    ];

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new EnvSettingsView();

        try {
            $pdo        = DBConnection::getPdo();
            $authorized = (new AdminGuard($pdo))->isAdmin(
                isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
            );

            $this->view->authorized = $authorized;
            $this->view->envPath    = self::ENV_PATH;
            $this->view->envWritable = is_file(self::ENV_PATH)
                ? is_writable(self::ENV_PATH)
                : is_writable(dirname(self::ENV_PATH));

            if (!$authorized) {
                return;
            }

            $appKey    = (string) (getenv('APP_KEY') ?: '');
            $encryptor = self::resolveEncryptor($appKey);

            $settingService = new PdoSystemSettingService($pdo, $encryptor);
            $changedBy      = isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : 'admin';

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $err = self::handlePost($settingService, $post, $changedBy);
                if ($err === null) {
                    $redirectTo = strtok((string) ($_SERVER['REQUEST_URI'] ?? './admin/env-settings/'), '?');
                    header('Location: ' . $redirectTo . '?saved=1', true, 303);
                    exit;
                }
                $this->view->writeError = $err;
            }

            $this->view->env      = self::loadEnvValues();
            $this->view->settings = self::loadSettings($settingService);
            $this->view->saved    = isset($_GET['saved']);
        } catch (\Throwable $e) {
            error_log('[env-settings] load failed: ' . $e);
            $this->view->loadError = $e->getMessage();
        }
    }

    public function flow(): View
    {
        return $this->view ?? new EnvSettingsView();
    }

    /** @param array<string, mixed> $post */
    private static function handlePost(
        PdoSystemSettingService $settingService,
        array $post,
        string $changedBy,
    ): ?string {
        $envUpdates = [];
        foreach (self::ENV_KEYS as $key) {
            $field = strtolower($key);
            if (!array_key_exists($field, $post)) {
                continue;
            }
            $value = is_string($post[$field]) ? trim($post[$field]) : '';

            if (in_array($key, self::ENV_SECRETS, true) && $value === '') {
                // Empty secret = keep current value.
                continue;
            }
            if ($key === 'APP_HTTPS') {
                $envUpdates[$key] = !empty($post[$field]) ? 'true' : 'false';
                continue;
            }
            $envUpdates[$key] = $value;
        }

        if (!empty($envUpdates)) {
            if (!EnvWriter::setMany(self::ENV_PATH, $envUpdates)) {
                return '.env への書き込みに失敗しました。ファイル権限を確認してください。';
            }
        }

        // Persist auth0 / firebase to system_setting so they take effect
        // immediately without restarting the PHP-FPM worker.
        $reason = 'Updated via Env Settings UI';
        if (!empty($post['auth0_domain'])) {
            $settingService->set(new SettingKey('auth0.domain'),    SettingValue::string(trim((string) $post['auth0_domain'])),    $changedBy, $reason);
        }
        if (!empty($post['auth0_client_id'])) {
            $settingService->set(new SettingKey('auth0.clientId'),  SettingValue::string(trim((string) $post['auth0_client_id'])),  $changedBy, $reason);
        }
        if (!empty($post['auth0_client_secret'])) {
            $settingService->set(new SettingKey('auth0.clientSecret'), SettingValue::secret(trim((string) $post['auth0_client_secret'])), $changedBy, $reason);
        }

        foreach ([
            'firebase_project_id'          => 'firebase.project_id',
            'firebase_auth_domain'         => 'firebase.auth_domain',
            'firebase_storage_bucket'      => 'firebase.storage_bucket',
            'firebase_messaging_sender_id' => 'firebase.messaging_sender_id',
            'firebase_app_id'              => 'firebase.app_id',
        ] as $postKey => $settingKey) {
            if (isset($post[$postKey])) {
                $settingService->set(new SettingKey($settingKey), SettingValue::string(trim((string) $post[$postKey])), $changedBy, $reason);
            }
        }
        if (!empty($post['firebase_api_key'])) {
            $settingService->set(new SettingKey('firebase.api_key'), SettingValue::secret(trim((string) $post['firebase_api_key'])), $changedBy, $reason);
        }
        return null;
    }

    private static function resolveEncryptor(string $appKey): SecretEncryptor
    {
        if ($appKey !== '') {
            $raw = base64_decode($appKey, true);
            if ($raw !== false && strlen($raw) === 32) {
                return new SecretEncryptor($raw);
            }
            if (preg_match('/^[0-9a-fA-F]{64}$/', $appKey)) {
                $hex = hex2bin($appKey);
                if ($hex !== false && strlen($hex) === 32) {
                    return new SecretEncryptor($hex);
                }
            }
            return new SecretEncryptor(hash('sha256', $appKey, binary: true));
        }
        return new SecretEncryptor(str_repeat("\x00", 32));
    }

    /** @return array<string, string> */
    private static function loadEnvValues(): array
    {
        $env = EnvLoader::loadFile(self::ENV_PATH);
        $out = [];
        foreach (self::ENV_KEYS as $key) {
            $value = $env[$key] ?? (string) (getenv($key) ?: '');
            $out[$key] = $value;
        }
        return $out;
    }

    /** @return array<string, mixed> */
    private static function loadSettings(PdoSystemSettingService $svc): array
    {
        $keys = [
            'auth0.domain', 'auth0.clientId', 'auth0.clientSecret',
            'firebase.project_id', 'firebase.api_key', 'firebase.auth_domain',
            'firebase.storage_bucket', 'firebase.messaging_sender_id', 'firebase.app_id',
        ];
        $out = [];
        foreach ($keys as $key) {
            $val = $svc->get(new SettingKey($key));
            $out[$key] = $val !== null ? $val->asString() : '';
        }
        return $out;
    }
}
