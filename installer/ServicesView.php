<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

/**
 * Optional wizard step. Lets the operator pre-seed Auth0 and Firebase
 * credentials so the post-install UI is ready to use immediately, but
 * "skip" is fully supported and is the default if the boxes are left
 * empty.
 *
 * Values land in `system_setting`, the same table the admin console
 * later reads / writes.
 */
final class ServicesView implements View
{
    use Setter;

    private string $title = '外部サービス連携 (任意)';
    private \Closure $content;

    public string $auth0Domain         = '';
    public string $auth0ClientId       = '';
    public string $auth0ClientSecret   = '';
    public string $firebaseProjectId   = '';
    public string $firebaseApiKey      = '';
    public string $firebaseAuthDomain  = '';
    public string $firebaseStorage     = '';
    public string $firebaseSenderId    = '';
    public string $firebaseAppId       = '';
    public ?string $errorMessage = null;

    public function display(): void
    {
        if (WizardState::installationComplete()) {
            http_response_code(410);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Installer is locked: this server is already installed.';
            return;
        }

        $env = WizardState::loadEnv();
        $pdo = WizardState::tryConnect($env);
        if ($pdo === null) {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/database/', true, 303);
            exit;
        }

        // The services step encrypts auth0/firebase secrets via SecretEncryptor.
        // If APP_KEY is missing or malformed, the previous code silently fell
        // back to an all-zero AES key — the exact configuration AppKeyResolver
        // refuses to boot with (SASO-INFRA-9000). Send the operator back to
        // the security step instead so they finish generating a real key
        // before any secret lands in system_setting under a zero-key cipher.
        //
        // Note: we pass the explicit value parsed from .env rather than
        // relying on getenv(), because the just-written wizard value has not
        // been re-injected into the process env this request.
        $rawKey = AppKeyResolver::tryResolve((string) ($env['APP_KEY'] ?? ''));
        if ($rawKey === null) {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/security/?error=app_key', true, 303);
            exit;
        }
        $encryptor = new SecretEncryptor($rawKey);
        $settingService = new PdoSystemSettingService($pdo, $encryptor);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($settingService);
            if ($this->errorMessage === null) {
                $base = self::baseUrl();
                header('Location: ' . $base . 'installer/admin/', true, 303);
                exit;
            }
        } else {
            $this->loadFromSettings($settingService);
        }
        require_once 'installer/template/services.php';
    }

    private function loadFromSettings(PdoSystemSettingService $svc): void
    {
        $get = static fn (string $key): string => ($v = $svc->get(new SettingKey($key))) ? $v->asString() : '';
        $this->auth0Domain        = $get('auth0.domain');
        $this->auth0ClientId      = $get('auth0.clientId');
        $this->auth0ClientSecret  = $get('auth0.clientSecret');
        $this->firebaseProjectId  = $get('firebase.project_id');
        $this->firebaseApiKey     = $get('firebase.api_key');
        $this->firebaseAuthDomain = $get('firebase.auth_domain');
        $this->firebaseStorage    = $get('firebase.storage_bucket');
        $this->firebaseSenderId   = $get('firebase.messaging_sender_id');
        $this->firebaseAppId      = $get('firebase.app_id');
    }

    private function handlePost(PdoSystemSettingService $svc): void
    {
        $reason = 'Saved via installer wizard';
        $by     = 'installer';

        try {
            $this->setString($svc, 'auth0.domain',                 $_POST['auth0_domain']          ?? '', $by, $reason);
            $this->setString($svc, 'auth0.clientId',               $_POST['auth0_client_id']       ?? '', $by, $reason);
            $secret = trim((string)($_POST['auth0_client_secret'] ?? ''));
            if ($secret !== '') {
                $svc->set(new SettingKey('auth0.clientSecret'), SettingValue::secret($secret), $by, $reason);
                $this->auth0ClientSecret = $secret;
            }

            $this->setString($svc, 'firebase.project_id',          $_POST['firebase_project_id']         ?? '', $by, $reason);
            $apiKey = trim((string)($_POST['firebase_api_key'] ?? ''));
            if ($apiKey !== '') {
                $svc->set(new SettingKey('firebase.api_key'), SettingValue::secret($apiKey), $by, $reason);
                $this->firebaseApiKey = $apiKey;
            }
            $this->setString($svc, 'firebase.auth_domain',         $_POST['firebase_auth_domain']        ?? '', $by, $reason);
            $this->setString($svc, 'firebase.storage_bucket',      $_POST['firebase_storage_bucket']     ?? '', $by, $reason);
            $this->setString($svc, 'firebase.messaging_sender_id', $_POST['firebase_messaging_sender_id'] ?? '', $by, $reason);
            $this->setString($svc, 'firebase.app_id',              $_POST['firebase_app_id']             ?? '', $by, $reason);

            // Mirror the plaintext fields back to the view so a re-render
            // (e.g. partial failure) shows what was just submitted.
            $this->auth0Domain        = (string)($_POST['auth0_domain']                 ?? '');
            $this->auth0ClientId      = (string)($_POST['auth0_client_id']              ?? '');
            $this->firebaseProjectId  = (string)($_POST['firebase_project_id']          ?? '');
            $this->firebaseAuthDomain = (string)($_POST['firebase_auth_domain']         ?? '');
            $this->firebaseStorage    = (string)($_POST['firebase_storage_bucket']      ?? '');
            $this->firebaseSenderId   = (string)($_POST['firebase_messaging_sender_id'] ?? '');
            $this->firebaseAppId      = (string)($_POST['firebase_app_id']              ?? '');
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-installer] services step failed: ' . $e->getMessage());
            }
            $this->errorMessage = '保存中にエラーが発生しました。サーバーログを確認してください。';
        }
    }

    private function setString(PdoSystemSettingService $svc, string $key, string $value, string $by, string $reason): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $svc->set(new SettingKey($key), SettingValue::string($value), $by, $reason);
    }

    private static function baseUrl(): string
    {
        $programDir = $_SERVER['SCRIPT_NAME'] ?? '';
        $programDir = trim(dirname($programDir), '/');
        return '/' . ($programDir !== '' ? $programDir . '/' : '');
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
        return $this->content ?? fn () => null;
    }
}
