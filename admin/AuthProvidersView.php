<?php
namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;

final class AuthProvidersView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public array $providers = [];

    public function display(): void
    {
        require_once 'admin/template/auth-providers.php';
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
