<?php
namespace saso;

use saso\framework\Router;
use saso\framework\UserCompiler;

mb_internal_encoding('UTF-8');
const ENV = null;
require_once 'ConfigLoader.php';
$config = ConfigLoader::load();

// --- M1 security hotfix: HTTPS enforcement -----------------------------------
// When config.https is true, redirect plain-HTTP requests to https:// and emit
// HSTS so future visits skip the redirect. Honors X-Forwarded-Proto so the
// check works behind reverse proxies / load balancers.
$onHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (!empty($config['https']) && !$onHttps) {
    header('Location: https://'.($_SERVER['HTTP_HOST'] ?? '').($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}
if (!empty($config['https'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$fullPath = $config['documentRoot'].$config['programDir'];
require_once 'ClassLoader.php';
spl_autoload_register(ClassLoader::load($config));

// --- M1 security hotfix: session cookie hardening ---------------------------
// HttpOnly blocks document.cookie reads, SameSite=Lax mitigates CSRF on
// top-level navigations, and Secure is set whenever config.https is true so
// the session id never leaves a TLS channel.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($config['https']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$authed = isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time();
if($authed){
    $_SESSION['time'] = time();
}
$route = json_decode(file_get_contents($fullPath.'request.json'), true);
$flow = json_decode(file_get_contents($fullPath.'flow.json'), true);
$installerRoute = $fullPath.'installer/installer.json';
if(file_exists($installerRoute)) {
    $installer = json_decode(file_get_contents($installerRoute), true);
} else {
    $installer = [];
}

$input = new UserCompiler(
    $_SERVER['REQUEST_URI'],
    json_decode(file_get_contents('php://input'), true)??$_POST,
    $config,
    $authed,
    new \DateTime(),
);
$router = new Router(
    array_merge($route, $installer),
);
$view = $router->route($input)->flow($flow);
$view->display();
$view->getContent()($view);
