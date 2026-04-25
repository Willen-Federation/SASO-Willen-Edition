<?php
namespace saso\label;

use saso\framework\View;
use saso\framework\Setter;

final class EditView implements View
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
        require_once 'label/template/edit.php';
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

