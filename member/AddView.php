<?php
namespace saso\member;

use saso\framework\Setter;
use saso\framework\View;

final class AddView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public string $error = '';

    public function display(): void
    {
        require_once 'member/template/add.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title ?? 'Register Member';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
