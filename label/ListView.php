<?php
namespace saso\label;

use saso\framework\Setter;
use saso\framework\View;

final class ListView implements View
{
    use Setter;
    private \Closure $content;
    private \Generator $labels;
    public function display(): void
    {
        require_once 'label/template/list.php';
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

