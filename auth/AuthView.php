<?php
namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;

final class AuthView implements View
{
    use Setter;
    protected string $title;
    protected \Closure $content;
    protected string $restoredPath;
    protected bool $isError;
    /** @var list<array{id:string,name:string,flavor:string,type:string}> */
    public array $idpProviders = [];
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
