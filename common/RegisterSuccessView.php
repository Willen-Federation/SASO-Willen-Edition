<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;
use saso\util;

final class RegisterSuccessView implements View
{
    use Setter;
    private string $to;
    public function display(): void
    {
        util\Redirect::redirect($this->to);
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
