<?php
namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;
use saso\util;

final class LogoutView implements View
{
    use Setter;
    public function display(): void
    {
        session_destroy();
        session_start();
        $_SESSION = [];
        if(isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/saso/',
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        util\Redirect::redirect();
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
