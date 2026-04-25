<?php
namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;

final class PasswordView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public function __construct(
        private bool $changed,
        private bool $errorNow,
    )
    {
    }
    public function display(): void
    {
        require_once 'auth/template/password.php';
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
