<?php

namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;

/**
 * @method \Closure restoredPath(\Closure $setter)
 * @method \Closure isError(\Closure $setter)
 * @method \Closure providers(\Closure $setter)
 */
final class AuthView implements View
{
    use Setter;
    protected string $title;
    protected \Closure $content;
    protected string $restoredPath;
    protected bool $isError;
    /** @var array<int, object> */
    protected array $providers = [];
    public function display(): void
    {
        require_once 'auth/template/auth.php';
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
