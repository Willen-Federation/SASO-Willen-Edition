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

// --- M2 autoload bridge ------------------------------------------------------
// New code under src/ uses the PSR-4 prefix `Saso\\` and is loaded by
// Composer's autoloader when vendor/ is present. Legacy code under the
// repository root continues to use the lowercase `saso\\` namespace and is
// loaded by ClassLoader.php. Both autoloaders are registered together via
// spl_autoload_register, so PHP queries them in turn until a class resolves.
if (is_file(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
}
require_once 'ClassLoader.php';
spl_autoload_register(ClassLoader::load($config));

// --- M3 REST API surface ----------------------------------------------------
// Requests under /api/v1/* are handled by the schema-first router declared
// in config/openapi.yaml (cf. ADR 0002). Legacy screens, the installer, and
// every existing PHP page continue to fall through to the request.json
// router below.
$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
if (str_starts_with($requestPath, '/api/v1/') || $requestPath === '/api/v1') {
    \Saso\Presentation\Api\V1\Bootstrap::dispatch(
        \Saso\Presentation\Api\V1\HttpRequest::fromGlobals(),
    );
    exit;
}

// --- M6-I MCP endpoint ------------------------------------------------------
// POST /mcp — JSON-RPC 2.0 Model Context Protocol server (cf. ADR 0014).
// Any HTTP method other than POST receives a 405.
if ($requestPath === '/mcp') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32600, 'message' => 'Method not allowed — use POST']]);
        exit;
    }
    \Saso\Presentation\Mcp\Bootstrap::dispatch();
    exit;
}

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

// --- M6-J3 i18n bootstrap (legacy path) -------------------------------------
// Bind the translator before the legacy router runs so views can call __().
// Locale precedence: ?lang= (explicit override) > saso_locale cookie >
// Accept-Language > default. The cookie is written by the language-switcher
// POST endpoint at /locale/set/{lc}; see authentication-agnostic short-circuit
// below.
if (class_exists(\Saso\Infrastructure\Translation\TranslatorFactory::class)) {
    $translator = \Saso\Infrastructure\Translation\TranslatorFactory::create();
    \Saso\Infrastructure\Translation\TranslatorRegistry::set($translator);

    $localeResolver = new \Saso\Presentation\Http\I18n\LocaleResolver();
    $resolvedLocale = $localeResolver->resolve(
        queryLang:      isset($_GET['lang'])     ? (string) $_GET['lang']     : null,
        memberLocale:   $_SESSION['locale']      ?? null,
        acceptLanguage: $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
        cookieLocale:   $_COOKIE['saso_locale']  ?? null,
    );
    $translator->setLocale($resolvedLocale);
}

// --- Language switcher endpoint (POST /locale/set/{lc}) ---------------------
// Writes the saso_locale cookie and 303-redirects back. Lives outside the
// legacy router because it has to short-circuit before any auth gating —
// the switcher is reachable from the login screen too.
if (preg_match('#^/locale/set/([a-z]{2})/?$#', $requestPath, $m)) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit;
    }
    $newLocale = $m[1];
    if (in_array($newLocale, ['en', 'ja'], true)) {
        setcookie('saso_locale', $newLocale, [
            'expires'  => time() + 365 * 86400,
            'path'     => '/',
            'secure'   => !empty($config['https']),
            'httponly' => false,  // readable by JS for client-side i18n
            'samesite' => 'Lax',
        ]);
    }
    $return = (string) ($_POST['return'] ?? $_SERVER['HTTP_REFERER'] ?? '/');
    // Defend against open-redirect: only allow same-origin paths.
    if (!preg_match('#^/[^/\\\\]#', $return)) {
        $return = '/';
    }
    header('Location: ' . $return, true, 303);
    exit;
}

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
