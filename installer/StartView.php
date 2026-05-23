<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Entry page of the install wizard (the route every other handler
 * redirects to when nothing else is preselected).
 *
 * Runs a quick environment self-check (PHP version, extensions,
 * filesystem writability) and shows the operator the path through the
 * remaining steps. From here a click on "次へ" hops straight to whichever
 * step {@see WizardState::nextStep()} says is unresolved, so a resumed
 * install does not force them to walk through screens that are already
 * green.
 */
final class StartView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;

    /** @var list<array{label: string, ok: bool, detail: string}> */
    public array $checks = [];

    public string $nextStep = WizardState::STEP_DATABASE;

    /** Preflight result captured for the template. */
    public ?Preflight $preflight = null;

    public function display(): void
    {
        $this->title = 'SASO セットアップ';

        // Preflight gates the entire wizard. If the filesystem is not in a
        // state where we can write `.env`, the wizard would otherwise advance
        // happily and crash on the security step. Render a dedicated failure
        // page so the operator gets actionable chmod/chown commands instead
        // of "0 bytes written" surprises later.
        $this->preflight = Preflight::run(WizardState::envPath());
        if (!$this->preflight->isOk()) {
            $this->title = 'インストール前提条件エラー';
            require_once 'installer/template/preflight_failed.php';
            return;
        }

        $this->checks = self::runChecks();
        $this->nextStep = WizardState::nextStep();
        require_once 'installer/template/start.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'SASO セットアップ';
    }

    public function getContent(): \Closure
    {
        return $this->content ?? fn () => null;
    }

    /**
     * @return list<array{label: string, ok: bool, detail: string}>
     */
    private static function runChecks(): array
    {
        $checks = [];

        $phpVersion = PHP_VERSION;
        $checks[] = [
            'label'  => 'PHP バージョン (>= 8.2)',
            'ok'     => PHP_VERSION_ID >= 80200,
            'detail' => 'インストール中: ' . $phpVersion,
        ];

        foreach (['pdo_mysql', 'mbstring', 'openssl', 'json', 'curl', 'gd'] as $ext) {
            $checks[] = [
                'label'  => 'PHP 拡張: ' . $ext,
                'ok'     => extension_loaded($ext),
                'detail' => extension_loaded($ext) ? '有効' : 'php.ini に追加してください',
            ];
        }

        $envPath = WizardState::envPath();
        $envWritable = is_file($envPath) ? is_writable($envPath) : is_writable(dirname($envPath));
        $checks[] = [
            'label'  => '`.env` への書き込み権限',
            'ok'     => $envWritable,
            'detail' => $envWritable
                ? 'ウィザードから設定を保存できます'
                : 'ファイル / ディレクトリを Web サーバから書き込めるようにしてください: ' . $envPath,
        ];

        $installerWritable = is_writable(__DIR__);
        $checks[] = [
            'label'  => 'インストーラディレクトリの操作権限',
            'ok'     => $installerWritable,
            'detail' => $installerWritable
                ? 'インストール完了時に installer フォルダを安全に削除できます'
                : 'インストール完了後に手動で installer/ を削除してください',
        ];

        return $checks;
    }
}
