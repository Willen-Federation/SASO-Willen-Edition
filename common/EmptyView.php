<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;

final class EmptyView implements View
{
    use Setter;
    public function display(): void
    {
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
        return fn()=>null;
    }
}
