<?php
namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Auth\AdminGuard;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class FirebaseSettingsDIContainer implements DIContainer
{
    private FirebaseSettingsView $view;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new FirebaseSettingsView();

        try {
            $pdo        = DBConnection::getPdo();
            $authorized = (new AdminGuard($pdo))->isAdmin(
                isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
            );

            $this->view->authorized = $authorized;

            if (!$authorized) {
                return;
            }

            $appKey    = (string) (getenv('APP_KEY') ?: '');
            $encryptor = new SecretEncryptor(str_repeat("\x00", 32));
            if ($appKey !== '') {
                $rawKey = base64_decode($appKey, true);
                if ($rawKey !== false && strlen($rawKey) === 32) {
                    $encryptor = new SecretEncryptor($rawKey);
                }
            }

            $settingService = new PdoSystemSettingService($pdo, $encryptor);
            $changedBy      = isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : 'admin';

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // If handlePost throws, the catch below sets loadError and the
                // page re-renders with the error — the redirect is skipped, so
                // the admin never sees a false "?saved=1" success banner.
                self::handlePost($settingService, $post, $changedBy);
                $redirectTo = strtok((string) ($_SERVER['REQUEST_URI'] ?? './admin/firebase-settings/'), '?');
                header('Location: ' . $redirectTo . '?saved=1', true, 303);
                exit;
            }

            $this->view->settings = self::loadSettings($settingService);
        } catch (\Throwable $e) {
            error_log('[firebase-settings] load failed: ' . $e);
            $this->view->settings  = $this->view->settings ?: [];
            $this->view->loadError = $e->getMessage();
        }

        $this->view->saved = isset($_GET['saved']);
    }

    public function flow(): View
    {
        return $this->view ?? new FirebaseSettingsView();
    }

    /** @param array<string, mixed> $post */
    private static function handlePost(PdoSystemSettingService $settingService, array $post, string $changedBy): void
    {
        // Firebase API Key (Secret)
        if (isset($post['firebase_api_key']) && trim((string) $post['firebase_api_key']) !== '') {
            $settingService->set(
                new SettingKey('firebase.api_key'),
                SettingValue::secret(trim((string) $post['firebase_api_key'])),
                $changedBy,
                'Updated via Firebase Settings UI',
            );
        }

        // Firebase Auth Domain
        $settingService->set(
            new SettingKey('firebase.auth_domain'),
            SettingValue::string(trim((string) ($post['firebase_auth_domain'] ?? ''))),
            $changedBy,
            'Updated via Firebase Settings UI',
        );

        // Firebase Project ID
        $settingService->set(
            new SettingKey('firebase.project_id'),
            SettingValue::string(trim((string) ($post['firebase_project_id'] ?? ''))),
            $changedBy,
            'Updated via Firebase Settings UI',
        );

        // Firebase Storage Bucket
        $settingService->set(
            new SettingKey('firebase.storage_bucket'),
            SettingValue::string(trim((string) ($post['firebase_storage_bucket'] ?? ''))),
            $changedBy,
            'Updated via Firebase Settings UI',
        );

        // Firebase Messaging Sender ID
        $settingService->set(
            new SettingKey('firebase.messaging_sender_id'),
            SettingValue::string(trim((string) ($post['firebase_messaging_sender_id'] ?? ''))),
            $changedBy,
            'Updated via Firebase Settings UI',
        );

        // Firebase App ID
        $settingService->set(
            new SettingKey('firebase.app_id'),
            SettingValue::string(trim((string) ($post['firebase_app_id'] ?? ''))),
            $changedBy,
            'Updated via Firebase Settings UI',
        );
    }

    /** @return array<string, mixed> */
    private static function loadSettings(PdoSystemSettingService $settingService): array
    {
        $apiKeyVal      = $settingService->get(new SettingKey('firebase.api_key'));
        $authDomainVal  = $settingService->get(new SettingKey('firebase.auth_domain'));
        $projectIdVal   = $settingService->get(new SettingKey('firebase.project_id'));
        $storageVal     = $settingService->get(new SettingKey('firebase.storage_bucket'));
        $senderIdVal    = $settingService->get(new SettingKey('firebase.messaging_sender_id'));
        $appIdVal       = $settingService->get(new SettingKey('firebase.app_id'));

        return [
            'firebase_api_key'             => $apiKeyVal     !== null ? $apiKeyVal->asString() : '',
            'firebase_auth_domain'         => $authDomainVal !== null ? $authDomainVal->asString() : '',
            'firebase_project_id'          => $projectIdVal  !== null ? $projectIdVal->asString() : '',
            'firebase_storage_bucket'      => $storageVal    !== null ? $storageVal->asString() : '',
            'firebase_messaging_sender_id' => $senderIdVal   !== null ? $senderIdVal->asString() : '',
            'firebase_app_id'              => $appIdVal      !== null ? $appIdVal->asString() : '',
        ];
    }
}
