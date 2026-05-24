<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;
use saso\util\EnvWriter;

/**
 * Wizard step that captures the application-level security toggles —
 * APP_KEY (AES-256-GCM master key), JWT_SECRET, APP_HTTPS, and the
 * webhook secret. Pre-fills sensible defaults (random 32-byte values
 * for the secrets, `false` for HTTPS) so an operator can advance with a
 * single click on the happy path.
 *
 * Implementation note (PR-A2): the actual secret writes are delegated to
 * {@see SecurityStep}, which validates each value against the same 3-shape
 * rule {@see \Saso\Infrastructure\Auth\Crypto\AppKeyResolver} applies at
 * boot, writes atomically via {@see \saso\util\EnvWriter::setOrUpdate()},
 * and rolls `.env` back on any failure. This closes the gap that produced
 * SASO-INFRA-9000 on production fresh installs.
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
    public ?SecurityStepResult $lastResult = null;

    public function display(): void
    {
        if (WizardState::installationComplete()) {
            http_response_code(410);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Installer is locked: this server is already installed.';
            return;
        }

        // Preflight gates EVERY mutation step. If the operator landed here
        // before Start ran the gate (deep link, browser back/forward), we
        // still refuse to render the form.
        $preflight = Preflight::run(WizardState::envPath());
        if (!$preflight->isOk()) {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/start/', true, 303);
            exit;
        }

        $env = WizardState::loadEnv();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
        } else {
            // Do NOT pre-fill the existing secret values back into the form.
            // Echoing APP_KEY / JWT_SECRET / WEBHOOK_SECRET into HTML on every
            // GET turns the page into a credential viewer for anyone who can
            // reach /installer/security/ before the installer is locked.
            // The placeholder text already invites "leave blank to keep the
            // existing value" — the SecurityStep treats blank as "preserve".
            $this->appKey        = '';
            $this->jwtSecret     = '';
            $this->webhookSecret = '';
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
        $regenerate          = !empty($_POST['regenerate']);

        if (!WizardState::ensureEnvFile()) {
            $this->errorMessage = '.env の作成に失敗しました。Web サーバから書き込み可能か確認してください。';
            return;
        }

        $step   = new SecurityStep(new EnvWriter());
        $result = $step->apply(WizardState::envPath(), [
            SecurityStep::KEY_APP     => $this->appKey,
            SecurityStep::KEY_JWT     => $this->jwtSecret,
            SecurityStep::KEY_WEBHOOK => $this->webhookSecret,
        ], $regenerate);
        $this->lastResult = $result;

        if (!$result->isOk()) {
            $this->errorMessage = self::formatError($result);
            return;
        }

        // APP_HTTPS is a non-secret toggle handled separately so it can keep
        // its boolean string form (`true` / `false`) without going through
        // the secret validators.
        EnvWriter::set(WizardState::envPath(), 'APP_HTTPS', $this->appHttps ? 'true' : 'false');

        // Intentionally do NOT mirror the freshly-written secrets back into
        // the view. After a successful POST we 303-redirect away from this
        // step; on the unlikely re-render (e.g. browser back arrow) leaving
        // the fields blank avoids leaking the now-canonical APP_KEY /
        // JWT_SECRET / WEBHOOK_SECRET into HTML where view-source can read
        // them.

        $base = self::baseUrl();
        header('Location: ' . $base . 'installer/services/', true, 303);
        exit;
    }

    private static function formatError(SecurityStepResult $result): string
    {
        switch ($result->status) {
            case SecurityStepResult::STATUS_INVALID:
                return implode(' / ', $result->errors);
            case SecurityStepResult::STATUS_WRITE_FAILED:
                return sprintf(
                    '.env の書き込みに失敗しました (%s): %s — ロールバック済みです。',
                    $result->failedKey ?? '?',
                    $result->detail ?? 'unknown',
                );
            case SecurityStepResult::STATUS_SELFTEST_FAILED:
                return sprintf(
                    'セキュリティキーの自己検証に失敗しました (%s)。ロールバック済みです。docs/runbooks/repair-app-key.md を参照してください。',
                    $result->failedKey ?? '?',
                );
            default:
                return '不明なエラーが発生しました。';
        }
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
