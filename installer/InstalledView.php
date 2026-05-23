<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Success page rendered after the admin row has been written. Offers a
 * one-click "delete installer folder" button as well as the existing
 * manual delete instructions, and links onward to the login screen.
 *
 * Deletes `installer/installer.json` here (not in {@see AdminView}) so
 * the redirect that lands the user on this page still resolves through
 * the route table loaded earlier in the request lifecycle.
 */
final class InstalledView implements View
{
    use Setter;

    private string $title = 'インストール完了';
    private \Closure $content;

    public bool $installerStillPresent = true;
    public bool $lockSuccess = true;

    public function display(): void
    {
        $this->lockSuccess = WizardState::lockInstaller();
        $this->installerStillPresent = is_dir(__DIR__);
        require_once 'installer/template/installed.php';
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
