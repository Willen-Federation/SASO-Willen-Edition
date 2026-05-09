<?php
namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;

final class FirebaseSettingsView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;

    public bool $authorized = false;
    public bool $saved      = false;

    /** @var array<string, mixed> */
    public array $settings = [];

    public function display(): void
    {
        require_once 'admin/template/firebase-settings.php';
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
