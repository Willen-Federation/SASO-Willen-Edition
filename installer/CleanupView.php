<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

/**
 * POST-only endpoint that deletes the installer directory and then
 * redirects to the login screen. The optional cleanup step the user
 * actions from the "installation complete" page.
 *
 * Falls back to a styled error page if deletion fails (e.g. SELinux,
 * read-only mount). The administrator can still finish removal by
 * hand.
 */
final class CleanupView implements View
{
    use Setter;

    private string $title = 'installer フォルダの削除';
    private \Closure $content;

    public bool $success = false;
    public ?string $errorMessage = null;

    public function display(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/installed/', true, 303);
            exit;
        }

        // Refuse to delete the installer directory before the wizard has
        // actually finished. Without this, a stray POST to /installer/cleanup/
        // (manual curl, broken browser state) could wipe the wizard mid-flow
        // and leave the operator with no recovery surface.
        if (!WizardState::installationComplete()) {
            http_response_code(409);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Refusing to delete installer/: installation is not complete.';
            return;
        }

        if (!is_dir(__DIR__)) {
            $this->success = true;
        } else {
            $this->success = WizardState::deleteInstallerDir();
            if (!$this->success) {
                $this->errorMessage = 'installer/ フォルダの削除に失敗しました。Web サーバのプロセスから書き込めるか確認するか、手動で削除してください。';
            }
        }

        if ($this->success) {
            $base = self::baseUrl();
            // Send the operator to the login screen — the installer
            // routes no longer resolve anyway once the file is gone.
            header('Location: ' . $base . 'auth/start/', true, 303);
            exit;
        }

        require_once 'installer/template/cleanup.php';
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
