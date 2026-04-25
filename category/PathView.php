<?php
namespace saso\category;

use saso\framework\Setter;
use saso\framework\View;

final class PathView implements View
{
    use Setter;
    private \Closure $content;
    private string $path='';
    public function display(): void
    {
        header('Content-Type: application/json');
        require 'category/template/path.php';
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

