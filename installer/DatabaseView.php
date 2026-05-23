<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;
use saso\util\EnvLoader;

/**
 * Wizard step that collects MySQL/MariaDB connection details and
 * persists them to `.env`. Once the connection test passes the
 * operator is forwarded to the schema-creation step.
 */
final class DatabaseView implements View
{
    use Setter;

    private string $title = 'データベース設定';
    private \Closure $content;

    public string $dsn      = '';
    public string $user     = '';
    public string $password = '';
    public bool   $connected = false;
    public ?string $errorMessage = null;
    public bool   $submitted = false;

    public function display(): void
    {
        $env = WizardState::loadEnv();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
        } else {
            $this->dsn      = $env['DB_DSN']      ?? 'mysql:host=localhost;dbname=saso_db;charset=utf8mb4';
            $this->user     = $env['DB_USER']     ?? 'saso_user';
            $this->password = $env['DB_PASSWORD'] ?? '';
        }
        require_once 'installer/template/database.php';
    }

    private function handlePost(): void
    {
        $this->submitted = true;
        $this->dsn      = trim((string)($_POST['dsn']      ?? ''));
        $this->user     = trim((string)($_POST['user']     ?? ''));
        $this->password = (string)($_POST['password'] ?? '');

        if ($this->dsn === '' || $this->user === '') {
            $this->errorMessage = 'DSN とユーザー名は必須です。';
            return;
        }

        if (!preg_match('/^mysql:host=/', $this->dsn)) {
            $this->errorMessage = 'DSN は "mysql:host=..." の形式で入力してください。';
            return;
        }

        try {
            new \PDO($this->dsn, $this->user, $this->password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable $e) {
            $this->errorMessage = 'データベース接続に失敗しました: ' . $e->getMessage();
            return;
        }

        if (!WizardState::writeDbConfig($this->dsn, $this->user, $this->password)) {
            $this->errorMessage = '.env への書き込みに失敗しました。ファイル権限を確認してください。';
            return;
        }

        $this->connected = true;
        $base = self::baseUrl();
        header('Location: ' . $base . 'installer/schema/', true, 303);
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
