<?php

declare(strict_types=1);

namespace saso\installer;

use saso\entity\Member;
use saso\framework\Setter;
use saso\framework\View;

/**
 * Final wizard step. Captures the bootstrap administrator credentials,
 * inserts the row, and then closes the installer by deleting
 * `installer/installer.json` so subsequent requests stop being
 * redirected.
 */
final class AdminView implements View
{
    use Setter;

    private string $title = '管理者アカウント作成';
    private \Closure $content;

    public string $name     = '';
    public string $loginId  = '';
    public string $password = '';
    public string $passwordConfirm = '';
    public ?string $errorMessage = null;

    public function display(): void
    {
        // Lockout gate. Once a Member row exists this view must NEVER run
        // again: re-executing handlePost on a "live" server would let an
        // unauthenticated visitor add an admin account (or, with a duplicate
        // login id, probe which IDs already exist). The router stops dispatch
        // here as long as installer.json is gone, but the view independently
        // refuses too in case the file is restored.
        $env = WizardState::loadEnv();
        $pdo = WizardState::tryConnect($env);
        if ($pdo !== null && WizardState::adminExists($pdo) && WizardState::envHasSecurity($env)) {
            http_response_code(410);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Installer is locked: this server is already installed.';
            return;
        }
        if ($pdo === null) {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/database/', true, 303);
            exit;
        }
        if (!WizardState::schemaInstalled($pdo)) {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/schema/', true, 303);
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($pdo);
            if ($this->errorMessage === null) {
                // The installer.json file is deleted by InstalledView so
                // that this redirect still resolves on the next request.
                $base = self::baseUrl();
                header('Location: ' . $base . 'installer/installed/', true, 303);
                exit;
            }
        }
        require_once 'installer/template/admin.php';
    }

    private function handlePost(\PDO $pdo): void
    {
        $this->name            = trim((string)($_POST['name']     ?? ''));
        $this->loginId         = trim((string)($_POST['id']       ?? ''));
        $this->password        = (string)($_POST['password']      ?? '');
        $this->passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($this->name === '' || mb_strlen($this->name) > 50) {
            $this->errorMessage = 'お名前は 1 〜 50 文字で入力してください。';
            return;
        }
        if (!preg_match('/^[0-9a-zA-Z_\-]{8,20}$/', $this->loginId)) {
            $this->errorMessage = 'ログイン ID は半角英数および "-" "_" の 8 〜 20 文字で入力してください。';
            return;
        }
        if (!preg_match('/^[0-9a-zA-Z]{8,20}$/', $this->password)) {
            $this->errorMessage = 'パスワードは半角英数の 8 〜 20 文字で入力してください。';
            return;
        }
        if ($this->password !== $this->passwordConfirm) {
            $this->errorMessage = 'パスワードと確認用パスワードが一致しません。';
            return;
        }

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM Member WHERE id = :id');
            $stmt->execute(['id' => $this->loginId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->errorMessage = '既に同じログイン ID のアカウントが存在します。';
                return;
            }

            $insert = $pdo->prepare('INSERT INTO Member (id, password, userName, role) VALUES (:id, :pw, :name, :role)');
            $insert->execute([
                'id'   => $this->loginId,
                'pw'   => Member::hashPassword($this->password),
                'name' => $this->name,
                'role' => 'admin',
            ]);
        } catch (\Throwable $e) {
            // PDO exceptions can contain SQL fragments and column names that
            // hint at the schema layout. The installer runs unauthenticated,
            // so render a generic message and stash the detail server-side
            // for the operator's logs.
            if (function_exists('error_log')) {
                error_log('[saso-installer] admin create failed: ' . $e->getMessage());
            }
            $this->errorMessage = 'アカウント作成中にエラーが発生しました。サーバーログを確認してください。';
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
