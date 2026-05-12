<?php
namespace saso\itemAttribute;

use saso\framework\Setter;
use saso\framework\View;

final class ListView implements View
{
    use Setter;
    private \Closure $content;
    private array $definitions = [];

    public function display(): void
    {
        header('Content-Type: application/json');
        require_once 'itemAttribute/template/list.php';
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
