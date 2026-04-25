<?php
namespace saso\label;

use saso\framework\Setter;
use saso\framework\View;

final class ShortNameView implements View
{
    use Setter;
    private \Closure $content;
    private string $shortName;
    public function display(): void
    {
        header('Content-Type: application/json');
        require_once 'label/template/shortName.php';
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