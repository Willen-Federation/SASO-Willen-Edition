<?php
namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;
use saso\util;
use saso\util\CSRFtoken;

final class LoginView implements View
{
    use Setter;
    private string $restoredPath;
    public function display(): void
    {
        session_regenerate_id(true);
        CSRFtoken::rotate();
        util\Redirect::redirect($this->restoredPath);
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
