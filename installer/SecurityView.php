<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Wizard step that captures the application-level security toggles —
 * APP_KEY (AES-256-GCM master key), JWT_SECRET, APP_HTTPS, and the
 * webhook secret. Pre-fills sensible defaults (random 32-byte values
 * for the secrets, `false` for HTTPS) so an operator can advance with a
 * single click on the happy path.
 */
final class SecurityView implements View
{
    use Setter;

    private string $title = 'セキュリティ設定';
    private \Closure $content;

    public string $appKey         = '';
    public string $jwtSecret      = '';
    public string $webhookSecret  = '';
    public bool   $appHttps       = false;
    public ?string $errorMessage  = null;

    public function display(): void
    {
        $env = WizardState::loadEnv();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
        } else {
            $this->appKey        = $env['APP_KEY']        ?? WizardState::generateAppKey();
            $this->jwtSecret     = $env['JWT_SECRET']     ?? WizardState::generateHexSecret();
            $this->webhookSecret = $env['WEBHOOK_SECRET'] ?? WizardState::generateHexSecret();
            $this->appHttps      = filter_var($env['APP_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        require_once 'installer/template/security.php';
    }

    private function handlePost(): void
    {
        $this->appKey        = trim((string)($_POST['app_key']        ?? ''));
        $this->jwtSecret     = trim((string)($_POST['jwt_secret']     ?? ''));
        $this->webhookSecret = trim((string)($_POST['webhook_secret'] ?? ''));
        $this->appHttps      = !empty($_POST['app_https']);

        if (strlen($this->appKey) < 32) {
            $this->errorMessage = 'APP_KEY は 32 文字以上のランダム値を指定してください。';
            return;
        }

        $written = WizardState::writeSecurity([
            'APP_KEY'        => $this->appKey,
            'JWT_SECRET'     => $this->jwtSecret,
            'WEBHOOK_SECRET' => $this->webhookSecret,
            'APP_HTTPS'      => $this->appHttps ? 'true' : 'false',
        ]);
        if (!$written) {
            $this->errorMessage = '.env への書き込みに失敗しました。';
            return;
        }

        $base = self::baseUrl();
        header('Location: ' . $base . 'installer/services/', true, 303);
        exit;
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
