<?php
namespace saso\start;

use saso\framework\View;
use saso\framework\Setter;

final class StartView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'start/template/start.php';
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
