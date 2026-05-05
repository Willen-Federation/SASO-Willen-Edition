<?php
namespace saso\role;

use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public array $roles = [];

    public function display(): void
    {
        require_once 'role/template/start.php';
    }
    public function onRoot(): bool { return true; }
    public function getTitle(): string { return $this->title ?? 'Role Management'; }
    public function getContent(): \Closure { return $this->content; }
}
