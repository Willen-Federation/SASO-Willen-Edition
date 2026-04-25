<?php
namespace saso\shelf;

use saso\framework\Setter;
use saso\framework\View;

final class ShelvesView implements View
{
    use Setter;
    private \Generator $shelves;
    public function display(): void
    {
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return fn()=>$this->shelves;
    }
}