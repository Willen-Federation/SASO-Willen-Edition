<?php
namespace saso\label;

use saso\framework\Setter;
use saso\framework\View;

final class SvgView implements View
{
    use Setter;
    private \Closure $content;
    public function display(): void
    {
        require_once 'label/template/svg.php';
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

