<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Legacy `/installer/install/` endpoint preserved for old links in
 * documentation. Forwards every request to the new multi-step wizard.
 */
final class InstallView implements View
{
    use Setter;

    private string $title = 'インストール';
    private \Closure $content;

    public function display(): void
    {
        $base = self::baseUrl();
        header('Location: ' . $base . 'installer/start/', true, 303);
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
