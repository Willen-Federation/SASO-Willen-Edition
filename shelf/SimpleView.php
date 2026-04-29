<?php
namespace saso\shelf;

use saso\framework\Setter;
use saso\framework\View;

final class SimpleView implements View
{
    use Setter;
    public string $title;
    public \Closure $content;

    public function display(): void
    {
        require_once 'shelf/template/simple.php';
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
