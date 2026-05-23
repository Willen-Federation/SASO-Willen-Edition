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
 *
 * Implementation note (PR-A2): before deleting `installer.json`, we run a
 * {@see PostInstallSelfTest} against the freshly-written `.env`. If any
 * secret would still trip the boot-time validator we *do not* delete the
 * installer lock — the operator is sent back to the security step with a
 * structured error linking to `docs/runbooks/repair-app-key.md`. This is
 * the last safety net against the scenario PR-A2 is designed to prevent:
 * "wizard completed, /api/v1/* returns 500 SASO-INFRA-9000".
 */
final class InstalledView implements View
{
    use Setter;

    private string $title = 'インストール完了';
    private \Closure $content;

    public bool $installerStillPresent = true;
    public bool $lockSuccess = true;
    public ?SelfTestResult $selfTest = null;

    public function display(): void
    {
        // Run the self-test BEFORE deleting installer.json. If a secret would
        // crash the boot, we keep the wizard reachable so the operator can
        // hop back to the security step.
        $selfTest = new PostInstallSelfTest();
        $this->selfTest = $selfTest->run(WizardState::envPath());
        if (!$this->selfTest->ok) {
            // Do NOT delete installer.json. Render the failure inline so the
            // operator can see exactly which secret failed.
            $this->lockSuccess           = false;
            $this->installerStillPresent = is_dir(__DIR__);
            require_once 'installer/template/installed.php';
            return;
        }

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
