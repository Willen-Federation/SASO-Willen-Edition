<?php
namespace saso\label;

use saso\entity\Label;
use saso\framework\Setter;
use saso\framework\View;

final class SizeView implements View
{
    use Setter;
    private \Closure $content;
    private Label $label;
    public function display(): void
    {
        header('Content-Type: application/json');
        require_once 'label/template/size.php';
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