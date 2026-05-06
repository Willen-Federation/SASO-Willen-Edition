<?php
namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

final class InstalledView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;

    public function display(): void
    {
        // Load the template into memory (sets $this->title and $this->content).
        require_once 'installer/template/installed.php';

        // All installer files are now in PHP's memory — safe to remove the
        // directory so the installer cannot be re-run accidentally.
        self::removeDir(__DIR__);
    }

    /** Recursively delete a directory and all its contents. */
    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            $entry->isDir() ? @rmdir($entry->getRealPath()) : @unlink($entry->getRealPath());
        }
        @rmdir($dir);
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
        return $this->content;
    }
}
