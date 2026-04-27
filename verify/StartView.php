<?php
namespace saso\verify;

use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public ?array $verifyResults = null;
    public ?string $lastChecked = null;
    public int $totalItems = 0;
    public int $verifiedCount = 0;
    public int $discrepancyCount = 0;
    public int $unverifiedCount = 0;

    public function display(): void
    {
        require_once 'verify/template/start.php';
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
