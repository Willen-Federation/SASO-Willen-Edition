<?php
namespace saso;

use saso\framework\Router;
use saso\framework\UserCompiler;

mb_internal_encoding('UTF-8');
const ENV = null;
require_once 'ConfigLoader.php';
require_once 'util/EnvLoader.php';
require_once 'util/EnvWriter.php';
$config = ConfigLoader::load();

// --- First-run secret bootstrap ---------------------------------------------
// While the installer marker file is present (i.e. setup hasn't completed),
// auto-generate a secure APP_KEY into `.env` if it's missing or blank. This
// matches what `make up` / docker/entrypoint.sh do for the Docker stack, and
// gives the standard PHP install path the same zero-touch experience: the
// user uploads the project, hits /installer/start, and gets a working
// SecretEncryptor without ever editing `.env` by hand.
//
// Only runs when `.env` is writable AND `installer/installer.json` exists,
// so production deployments (where setup is already complete) never see
// runtime mutations to their environment file.
if (file_exists(__DIR__.'/installer/installer.json')) {
    $envPath = __DIR__.'/.env';
    if (!is_file($envPath) && is_file(__DIR__.'/.env.example') && is_writable(__DIR__)) {
        @copy(__DIR__.'/.env.example', $envPath);
    }
    if (is_file($envPath) && is_writable($envPath)) {
        $bag = util\EnvLoader::loadFile($envPath);
        if (empty($bag['APP_KEY']) && empty(getenv('APP_KEY'))) {
            $generated = base64_encode(random_bytes(32));
            if (util\EnvWriter::set($envPath, 'APP_KEY', $generated)) {
                putenv('APP_KEY='.$generated);
            }
        }
    }
}

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

// --- Helper safety net -------------------------------------------------------
// `ui()` and `__()` are normally registered via Composer's `files` autoload
// (composer.json: "files": ["framework/ui/helpers.php",
// "src/Infrastructure/Translation/functions.php"]). When `vendor/` is missing
// — typical on hosts that haven't run `composer install --no-dev` yet — that
// autoload never fires and any template calling `ui('card', …)` or
// `__('foo', [], null, 'Fallback')` would emit a fatal "undefined function"
// error and the page renders blank. We require the helper file directly
// (idempotent, has its own `function_exists` guard) and provide a stub `__()`
// that just returns the fallback. The translator-backed `__()` from
// functions.php — when present — is loaded BEFORE this block by the autoload
// above, so this branch only fires when the real helper truly isn't there.
require_once __DIR__ . '/framework/ui/helpers.php';
if (!function_exists('__')) {
    function __(string $key, array $params = [], ?string $locale = null, ?string $fallback = null): string {
        return $fallback ?? $key;
    }
}

// --- M3 REST API surface ----------------------------------------------------
// Requests under /api/v1/* are handled by the schema-first router declared
// in config/openapi.yaml (cf. ADR 0002). Legacy screens, the installer, and
// every existing PHP page continue to fall through to the request.json
// router below.
$rawUri      = (string) ($_SERVER['REQUEST_URI'] ?? '/');
// Normalize consecutive slashes (e.g. //auth/providers/ → /auth/providers/).
// parse_url('//foo/bar', PHP_URL_PATH) treats 'foo' as hostname and returns
// '/bar', causing mis-routing. Collapse them before parsing.
$rawUri      = preg_replace('#/{2,}#', '/', $rawUri) ?? $rawUri;
$requestPath = (string) (parse_url($rawUri, PHP_URL_PATH) ?? '/');

// Automatic fallback redirect for users accessing the old /saso/ path
if (str_starts_with($requestPath, '/saso/')) {
    $newUri = preg_replace('#^/saso/#', '/', $requestPath);
    $query = $_SERVER['QUERY_STRING'] ?? '';
    if ($query !== '') {
        $newUri .= '?' . $query;
    }
    $baseScheme = $onHttps ? 'https://' : 'http://';
    header('Location: ' . $baseScheme . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $newUri, true, 301);
    exit;
}

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

// --- Server-side git fetch webhook ------------------------------------------
// POST /webhock or /webhook
// Executes one fixed command path (`pull.sh` -> `git fetch origin`) only after
// validating WEBHOOK_SECRET from X-Webhook-Token. Do not accept command names,
// branch names, or other request-controlled shell input here.
if ($requestPath === '/webhock' || $requestPath === '/webhook') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo json_encode(['ok' => false, 'error' => 'Method not allowed; use POST']);
        exit;
    }

    $expectedToken = (string) (getenv('WEBHOOK_SECRET') ?: '');
    $providedToken = (string) ($_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '');

    if (strlen($expectedToken) < 32) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'Webhook secret is not configured']);
        exit;
    }

    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }

    $lock = fopen(sys_get_temp_dir() . '/saso-git-fetch-webhook.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Fetch already in progress']);
        exit;
    }

    $script = __DIR__ . '/pull.sh';
    if (!is_file($script) || !is_readable($script)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Webhook script is unavailable']);
        exit;
    }

    $output = [];
    $exitCode = 0;
    exec('/bin/bash ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);

    http_response_code($exitCode === 0 ? 200 : 500);
    echo json_encode([
        'ok' => $exitCode === 0,
        'exitCode' => $exitCode,
    ]);
    exit;
}

// --- Debug endpoints (GET /debug/ai-status, POST /debug/ai-probe) ----------
// Local-only AI debugging endpoints. Requires APP_DEBUG=true or .ENV file.
// GET /debug/ai-status — returns AI provider, key config, assistant class.
// POST /debug/ai-probe — accepts { image_base64, text }, runs AiVisionStep.
if (str_starts_with($requestPath, '/debug/ai-')) {
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    $debugContainer = new \saso\debug\AiDebugDIContainer();
    $debugContainer->di(
        static fn () => null,
        $_GET ?? [],
        $_POST ?? [],
        $config,
        $now,
    );
    $view = $debugContainer->flow();
    exit;
}

// --- M1 security hotfix: session cookie hardening ---------------------------
// HttpOnly blocks document.cookie reads, SameSite=Lax mitigates CSRF on
// top-level navigations, and Secure is set whenever config.https is true so
// the session id never leaves a TLS channel.
// Note: SameSite is omitted for non-HTTPS (dev) because cross-site OAuth
// redirects (e.g., from Auth0) won't send the session cookie with SameSite=Lax.
// On production (HTTPS), SameSite=None (with Secure) or Lax is safer. For dev
// (HTTP), we rely on HttpOnly + CSRF tokens instead.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($config['https']),
    'httponly' => true,
    'samesite' => !empty($config['https']) ? 'Lax' : '',
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

// --- M4-D2 external auth endpoints ------------------------------------------
// /auth/start/{providerId}    → redirect to IdP authorize endpoint
// /auth/callback               → handle IdP callback (provider ID from session)
// /auth/saml/acs               → SAML AssertionConsumerService POST (provider ID from session)
// /auth/saml/sls               → SAML SingleLogoutService (provider ID from session)
// All of these are wired via the LoginOrchestrator. They short-circuit the
// legacy router so the path tail (provider id) does not have to be encoded
// into request.json. Schema mismatches (M4 not migrated, no APP_KEY, etc.)
// fall through to the login screen with `?error=auth_unavailable`.
// NOTE: Provider ID is NOT exposed in callback URLs (security). For callback/acs/sls,
// the provider ID is retrieved from $_SESSION['auth.provider_id'] which was set
// during beginLogin().
if (preg_match('#^/auth/(?:start/(\d+)|callback|saml/acs|saml/sls)/?$#', $requestPath, $authMatch) === 1) {
    $authAction    = preg_match('#^/auth/start/#', $requestPath) === 1 ? 'start'
        : (preg_match('#/callback/?$#', $requestPath) === 1 ? 'callback'
        : (preg_match('#/saml/acs/?$#', $requestPath) === 1 ? 'acs' : 'sls'));

    // For 'start' action, extract provider ID from URL.
    // For callback/acs/sls actions, retrieve provider ID from session (set during beginLogin).
    if ($authAction === 'start') {
        $providerIdInt = (int) ($authMatch[1] ?? 0);
        if ($providerIdInt < 1) {
            throw new \RuntimeException('Provider ID missing from /auth/start/{id} URL.');
        }
    } else {
        // callback / acs / sls
        $providerIdInt = (int) ($_SESSION['auth.provider_id'] ?? 0);
        if ($providerIdInt < 1) {
            throw new \RuntimeException('Provider ID not found in session. Login must be initiated via /auth/start/{id}.');
        }
    }

    try {
        $appKey = (string) (getenv('APP_KEY') ?: '');
        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is not set; cannot bring up auth providers.');
        }
        $rawKey = base64_decode($appKey, true);
        if ($rawKey === false || strlen($rawKey) !== 32) {
            throw new RuntimeException('APP_KEY must be a base64-encoded 32-byte value.');
        }

        $pdo        = \saso\repository\DBConnection::getPdo();
        $encryptor  = new \Saso\Infrastructure\Auth\Crypto\SecretEncryptor($rawKey);
        $providers  = new \Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository($pdo, $encryptor);
        $extIds     = new \Saso\Infrastructure\Auth\Repository\PdoExternalIdentityRepository($pdo);
        $baseScheme = $onHttps ? 'https://' : 'http://';
        // Note: baseUrl for OAuth callbacks should NOT include programDir.
        // programDir is for file paths (documentRoot + programDir), not URL routing.
        // Apache's DocumentRoot is already /var/www/html/saso, so the app is served at /.
        $baseUrl    = $baseScheme.($_SERVER['HTTP_HOST'] ?? 'localhost');
        $factory    = new \Saso\Infrastructure\Auth\AuthProviderFactory($providers, $pdo, $baseUrl);
        $orch       = new \Saso\Application\Auth\LoginOrchestrator($factory, $extIds, $pdo);
        $providerId = new \Saso\Domain\Auth\AuthProviderId($providerIdInt);

        if ($authAction === 'start') {
            $returnTo = (string) ($_GET['return'] ?? '/');
            if ($returnTo !== '/' && preg_match('#^/[^/\\\\]#', $returnTo) !== 1) {
                $returnTo = '/';
            }
            $redirect = $orch->beginLogin($providerId, $returnTo);
            header('Location: '.$redirect->url, true, $redirect->status);
            exit;
        }

        // callback / acs / sls
        $callback = new \Saso\Domain\Auth\CallbackRequest(
            method:  $_SERVER['REQUEST_METHOD'] ?? 'GET',
            uri:     (string) ($_SERVER['REQUEST_URI'] ?? ''),
            query:   array_map('strval', $_GET),
            body:    array_map('strval', $_POST),
            headers: [],
        );

        if ($authAction === 'sls') {
            $logoutRedirect = $orch->beginLogout('./');
            $_SESSION = [];
            session_destroy();
            header('Location: '.($logoutRedirect?->url ?? './'), true, 303);
            exit;
        }

        $returnTo = $orch->handleCallback($providerId, $callback);
        header('Location: '.$returnTo, true, 303);
        exit;
    } catch (\Throwable $e) {
        // Log to whatever Monolog channel is reachable; rendering the raw
        // message would expose state to the user. Hand off to the legacy
        // login form with a generic error.
        if (function_exists('error_log')) {
            error_log('[saso-auth] '.$e->getMessage());
        }
        header('Location: ./auth/start?error=auth_unavailable', true, 303);
        exit;
    }
}

// --- Mobile setup endpoints (M6-K) ------------------------------------------
// /m/setup            → start the mobile pairing flow. Requires
//                       ?redirect_uri=<custom-scheme>&state=<csrf>. Validates
//                       redirect_uri against an allowlist, stashes both in the
//                       session, then redirects to /auth/start/{providerId}
//                       so the user authenticates with the configured IdP.
//                       Provider selection rules:
//                         - ?provider_id={id}   override (must be enabled)
//                         - exactly one default → that one
//                         - otherwise → simple chooser HTML
// /m/issue-pairing    → after /auth/callback succeeds (session established),
//                       generate a fresh PairingCode and 303-redirect to
//                       redirect_uri#token=<raw>&state=<state>&server=<base>.
//                       The Flutter app then exchanges via /api/v1/mobile/connect.
if ($requestPath === '/m/setup' || $requestPath === '/m/issue-pairing') {
    try {
        $appKey = (string) (getenv('APP_KEY') ?: '');
        if ($appKey === '') {
            throw new \RuntimeException('APP_KEY not configured');
        }
        $rawKey = base64_decode($appKey, true);
        if ($rawKey === false || strlen($rawKey) !== 32) {
            throw new \RuntimeException('APP_KEY must be base64 32 bytes');
        }
        $pdo       = \saso\repository\DBConnection::getPdo();
        $encryptor = new \Saso\Infrastructure\Auth\Crypto\SecretEncryptor($rawKey);
        $providers = new \Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository($pdo, $encryptor);
        $allowlist = \Saso\Application\Mobile\RedirectUriAllowlist::fromConfig($config);

        if ($requestPath === '/m/setup') {
            $redirectUri = trim((string) ($_GET['redirect_uri'] ?? ''));
            $stateParam  = trim((string) ($_GET['state'] ?? ''));
            $providerId  = trim((string) ($_GET['provider_id'] ?? ''));

            if ($redirectUri === '' || !$allowlist->isAllowed($redirectUri)) {
                http_response_code(400);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Bad Request: invalid or missing redirect_uri';
                exit;
            }
            if ($stateParam === '' || !preg_match('/^[A-Za-z0-9_\-]{16,128}$/', $stateParam)) {
                http_response_code(400);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Bad Request: invalid state (16-128 url-safe chars)';
                exit;
            }

            $_SESSION['mobile.setup.redirect_uri'] = $redirectUri;
            $_SESSION['mobile.setup.state']        = $stateParam;
            $_SESSION['mobile.setup.expires']      = time() + 600;

            $records  = $providers->listEnabled();
            $chosenId = null;
            if ($providerId !== '' && ctype_digit($providerId)) {
                foreach ($records as $rec) {
                    if ((string) $rec->id->value() === $providerId && $rec->enabled) {
                        $chosenId = $rec->id->value();
                        break;
                    }
                }
            }
            if ($chosenId === null) {
                $defaults = array_values(array_filter($records, fn ($r) => $r->isDefault));
                if (count($defaults) === 1) {
                    $chosenId = $defaults[0]->id->value();
                }
            }
            if ($chosenId !== null) {
                header('Location: /auth/start/' . $chosenId . '?return=' . urlencode('/m/issue-pairing'), true, 303);
                exit;
            }

            // No default — render chooser
            header('Content-Type: text/html; charset=utf-8');
            echo "<!doctype html><html lang=\"ja\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>SASO モバイル接続</title>"
                . '<style>body{font-family:system-ui,-apple-system,sans-serif;margin:0;padding:24px;background:#f5f5f7}'
                . 'h1{font-size:18px;margin:0 0 16px}.provider{display:block;background:#fff;border:1px solid #d2d2d7;border-radius:12px;padding:16px;margin-bottom:8px;text-decoration:none;color:#000}'
                . '.provider:hover{background:#fafafa}.type{font-size:11px;color:#666;text-transform:uppercase}</style></head><body>';
            echo '<h1>認証方法を選択してください</h1>';
            foreach ($records as $rec) {
                if (!$rec->enabled) continue;
                $href = '/auth/start/' . $rec->id->value() . '?return=' . urlencode('/m/issue-pairing');
                echo '<a class="provider" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
                    . '<div class="type">' . htmlspecialchars($rec->type->value, ENT_QUOTES, 'UTF-8') . '</div>'
                    . '<div>' . htmlspecialchars($rec->name, ENT_QUOTES, 'UTF-8') . '</div></a>';
            }
            if ($records === []) {
                echo '<p>このサーバーには認証プロバイダーが設定されていません。管理者にお問い合わせください。</p>';
            }
            echo '</body></html>';
            exit;
        }

        // /m/issue-pairing
        $authedNow = isset($_SESSION['id']) && isset($_SESSION['time']) && $_SESSION['time'] + 3600 > time();
        if (!$authedNow) {
            http_response_code(401);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Unauthorized: session not established';
            exit;
        }
        $redirectUri = (string) ($_SESSION['mobile.setup.redirect_uri'] ?? '');
        $stateParam  = (string) ($_SESSION['mobile.setup.state'] ?? '');
        $expires     = (int)    ($_SESSION['mobile.setup.expires'] ?? 0);
        unset(
            $_SESSION['mobile.setup.redirect_uri'],
            $_SESSION['mobile.setup.state'],
            $_SESSION['mobile.setup.expires'],
        );
        if ($redirectUri === '' || $stateParam === '' || $expires < time()) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Bad Request: setup session expired or missing';
            exit;
        }
        if (!$allowlist->isAllowed($redirectUri)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Bad Request: redirect_uri no longer allowed';
            exit;
        }

        $codeRepo = new \Saso\Infrastructure\MobileConnect\PdoPairingCodeRepository($pdo);
        $now      = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $expiry   = $now->modify('+5 minutes');
        $rawTok   = \Saso\Domain\MobileConnect\PairingCode::generateRawToken();
        $hash     = \Saso\Domain\MobileConnect\PairingCode::hashToken($rawTok);
        $label    = 'Mobile setup (member ' . (int) $_SESSION['id'] . ')';

        $code = new \Saso\Domain\MobileConnect\PairingCode(
            id: $codeRepo->nextId(),
            tokenHash: $hash,
            label: $label,
            used: false,
            expiresAt: $expiry,
            createdAt: $now,
        );
        $codeRepo->save($code);

        $proto   = $onHttps ? 'https' : 'http';
        $baseUrl = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $fragment = http_build_query([
            'token'  => $rawTok,
            'state'  => $stateParam,
            'server' => $baseUrl,
        ]);
        header('Location: ' . $redirectUri . '#' . $fragment, true, 303);
        exit;
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[saso-mobile-setup] ' . $e->getMessage());
        }
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Internal Server Error during mobile setup';
        exit;
    }
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
    $requestPath,
    json_decode(file_get_contents('php://input'), true)??$_POST,
    $config,
    $authed,
    new \DateTime(),
);

// --- Installer redirect (M2.4 setup-completion gate) -------------------------
// While installer/installer.json exists, the DB schema and/or initial admin
// account have not been provisioned. Authentication, the admin UI, and most
// legacy screens are guaranteed to fail. Force every non-installer page
// request to /installer/start/ so first-run operators land where they can act,
// instead of getting bounced into an inert login form they cannot complete.
// API (/api/v1/*) and protocol (/mcp) endpoints short-circuit earlier in this
// file and never reach this gate, which is correct: machine clients should
// receive structured error responses rather than HTML redirects.
if (file_exists($installerRoute)) {
    $first = $input->request()[0] ?? '';
    if ($first !== 'installer' && $first !== 'js' && $first !== 'css') {
        $programDir = trim((string) ($config['programDir'] ?? ''), '/');
        $base = '/' . ($programDir !== '' ? $programDir . '/' : '');
        header('Location: ' . $base . 'installer/start/', true, 302);
        exit;
    }
}

$router = new Router(
    array_merge($route, $installer),
);
$view = $router->route($input)->flow($flow);
$view->display();
$view->getContent()($view);
