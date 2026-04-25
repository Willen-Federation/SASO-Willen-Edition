<?php
namespace saso\start;

use saso\framework\Setter;
use saso\framework\View;

final class MenuView implements View
{
    use Setter;
    private \Closure $content;
    public function __construct(
    )
    {
    }
    public function display(): void
    {
        require_once 'start/template/menu.php';
    }
    public function onRoot(): bool
    {
        return false;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
