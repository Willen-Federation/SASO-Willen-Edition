<?php
namespace saso\member;

use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public array $members = [];

    public function display(): void
    {
        require_once 'member/template/start.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title ?? 'Members';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
