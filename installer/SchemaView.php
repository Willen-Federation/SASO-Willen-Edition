<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Runs the schema migration via Phinx (when available) or the legacy
 * `createTables.php` fallback. Tables already present are left alone so
 * the operator can re-enter the wizard without dropping data.
 */
final class SchemaView implements View
{
    use Setter;

    private string $title = 'スキーマ作成';
    private \Closure $content;

    public bool $alreadyInstalled = false;
    public bool $success = false;
    public ?string $errorMessage = null;
    /** @var list<string> */
    public array $log = [];

    public function display(): void
    {
        $env = WizardState::loadEnv();
        $pdo = WizardState::tryConnect($env);
        if ($pdo === null) {
            $base = self::baseUrl();
            header('Location: ' . $base . 'installer/database/', true, 303);
            exit;
        }

        $this->alreadyInstalled = WizardState::schemaInstalled($pdo);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->runMigration($pdo);
            if ($this->success) {
                $base = self::baseUrl();
                header('Location: ' . $base . 'installer/security/', true, 303);
                exit;
            }
        }

        require_once 'installer/template/schema.php';
    }

    private function runMigration(\PDO $pdo): void
    {
        try {
            $this->log[] = '基本テーブルを作成中...';
            $this->createLegacyTables($pdo);
            $this->log[] = '基本テーブルの作成が完了しました。';

            // Phinx migrations (M4-M7) — best-effort; ignore if not available.
            $this->log[] = 'Phinx マイグレーションを実行中...';
            $this->runPhinxIfAvailable();
            $this->log[] = 'マイグレーション処理が完了しました。';
            $this->success = true;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function createLegacyTables(\PDO $pdo): void
    {
        $statements = require __DIR__ . '/schemaStatements.php';
        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }
    }

    private function runPhinxIfAvailable(): void
    {
        $phinx = __DIR__ . '/../vendor/bin/phinx';
        if (!is_file($phinx) || !is_executable($phinx)) {
            $this->log[] = 'Phinx CLI が見つかりません。Composer 依存関係をインストール済みかご確認ください。';
            return;
        }
        $cwd = realpath(__DIR__ . '/..');
        if ($cwd === false) {
            return;
        }
        $cmd = sprintf('cd %s && %s migrate -e development 2>&1', escapeshellarg($cwd), escapeshellarg($phinx));
        $output = @shell_exec($cmd);
        if (is_string($output) && $output !== '') {
            foreach (preg_split('/\r?\n/', trim($output)) as $line) {
                if ($line !== '') {
                    $this->log[] = $line;
                }
            }
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
