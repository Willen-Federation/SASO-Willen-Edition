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
        $_SESSION = [];
        // Match the cookie attributes set in index.php (path=/, plus the
        // Secure/SameSite pair when serving over TLS) so the browser
        // actually overwrites the live cookie. The previous /saso/ path
        // dated from the legacy install layout and left the live session
        // cookie at "/" untouched.
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $params['path'] ?: '/',
                'domain'   => $params['domain'] ?? '',
                'secure'   => $params['secure'] ?? false,
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
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
