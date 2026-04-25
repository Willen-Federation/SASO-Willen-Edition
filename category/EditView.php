<?php
namespace saso\category;

use saso\framework\Setter;
use saso\framework\View;

final class EditView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public function display(): void
    {
        require_once 'category/template/edit.php';
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