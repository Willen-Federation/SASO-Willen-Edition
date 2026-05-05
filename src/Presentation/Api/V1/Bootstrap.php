<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use PDO;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use Saso\Infrastructure\MobileConnect\PdoPairingCodeRepository;
use Saso\Infrastructure\MobileConnect\QrCodeRenderer;
use Saso\Infrastructure\Translation\TranslatorFactory;
use Saso\Infrastructure\Translation\TranslatorRegistry;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagCreateController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagDeleteController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagGetController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagListController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagUpdateController;
use Saso\Presentation\Api\V1\Controller\HealthController;
use Saso\Presentation\Api\V1\Controller\Mobile\ConfigBundleController;
use Saso\Presentation\Api\V1\Controller\Mobile\ConnectController;
use Saso\Presentation\Api\V1\Controller\Mobile\QrController;
use Saso\Presentation\Api\V1\Controller\Mobile\TokenListController;
use Saso\Presentation\Api\V1\Controller\Mobile\TokenRefreshController;
use Saso\Presentation\Api\V1\Controller\Mobile\TokenRevokeController;
use Saso\Presentation\Api\V1\Controller\OpenApiController;
use Saso\Presentation\Api\V1\Controller\SwaggerUiController;
use Saso\Presentation\Http\I18n\LocaleResolver;
use Saso\Presentation\Http\Problem\ProblemExceptionHandler;
use Saso\Presentation\Http\Problem\ProblemRenderer;

/**
 * Composition root for `/api/v1/*`.
 *
 * Called from the legacy `index.php` whenever the request path falls
 * inside the API surface. Owns the wiring of the OpenAPI spec loader,
 * translator, locale resolver, exception handler, and controller map —
 * legacy code never sees these classes.
 */
final class Bootstrap
{
    public static function dispatch(HttpRequest $request): void
    {
        $logger     = MonologFactory::create();
        $translator = TranslatorFactory::create();
        TranslatorRegistry::set($translator);

        $resolver = new LocaleResolver();
        $locale   = $resolver->resolve(
            queryLang: $request->query['lang'] ?? null,
            acceptLanguage: $request->header('accept-language'),
        );

        $exceptionHandler = new ProblemExceptionHandler(
            logger: $logger,
            renderer: new ProblemRenderer(),
            debug: false,
            translator: $translator,
        );

        $spec     = OpenApiSpec::load(self::specPath());
        $handlers = self::handlerMap($spec);

        $router = new Router($spec, $handlers, $exceptionHandler);
        $router->dispatch($request, $locale);
    }

    /**
     * @return array<string, callable(HttpRequest): \Saso\Presentation\Api\V1\Response\HttpResponse>
     */
    private static function handlerMap(OpenApiSpec $spec): array
    {
        $health    = new HealthController();
        $openApi   = new OpenApiController($spec);
        $swaggerUi = new SwaggerUiController();

        $pdo = self::createPdo();
        $jwt = new JwtService(self::jwtSecret());

        $flagRepo   = new PdoFeatureFlagRepository($pdo);
        $codeRepo   = new PdoPairingCodeRepository($pdo);
        $tokenRepo  = new PdoDeviceTokenRepository($pdo);
        $qrRenderer = new QrCodeRenderer();

        $flagList   = new FeatureFlagListController($flagRepo);
        $flagCreate = new FeatureFlagCreateController($flagRepo);
        $flagGet    = new FeatureFlagGetController($flagRepo);
        $flagUpdate = new FeatureFlagUpdateController($flagRepo);
        $flagDelete = new FeatureFlagDeleteController($flagRepo);

        $qr           = new QrController($codeRepo, $qrRenderer);
        $connect      = new ConnectController($codeRepo, $tokenRepo, $jwt);
        $configBundle = new ConfigBundleController($flagRepo);
        $tokenList    = new TokenListController($tokenRepo);
        $tokenRevoke  = new TokenRevokeController($tokenRepo);
        $tokenRefresh = new TokenRefreshController($tokenRepo, $jwt);

        return [
            'getHealth'       => [$health, 'handle'],
            'getOpenApiSpec'  => [$openApi, 'yaml'],
            'getSwaggerUi'    => [$swaggerUi, 'page'],

            'listFeatureFlags'  => [$flagList, 'handle'],
            'createFeatureFlag' => static function (HttpRequest $r) use ($flagCreate) { self::requireSessionAuth(); return $flagCreate->handle($r); },
            'getFeatureFlag'    => [$flagGet, 'handle'],
            'updateFeatureFlag' => static function (HttpRequest $r) use ($flagUpdate) { self::requireSessionAuth(); return $flagUpdate->handle($r); },
            'deleteFeatureFlag' => static function (HttpRequest $r) use ($flagDelete) { self::requireSessionAuth(); return $flagDelete->handle($r); },

            'createPairingCode' => [$qr, 'handle'],
            'mobileConnect'     => [$connect, 'handle'],
            'refreshMobileToken' => [$tokenRefresh, 'handle'],
            'getMobileConfig'   => [$configBundle, 'handle'],
            'listDeviceTokens'  => [$tokenList, 'handle'],
            'revokeDeviceToken' => [$tokenRevoke, 'handle'],
        ];
    }

    public static function specPath(): string
    {
        return dirname(__DIR__, 4).'/config/openapi.yaml';
    }

    private static function requireSessionAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $authed = isset($_SESSION['id'], $_SESSION['time']) && $_SESSION['time'] + 3600 > time();
        if (!$authed) {
            http_response_code(401);
            header('Content-Type: application/problem+json; charset=utf-8');
            echo json_encode(['status' => 401, 'title' => 'Unauthorized', 'detail' => 'Admin session required.']);
            exit;
        }
    }

    private static function createPdo(): PDO
    {
        if (class_exists(\saso\repository\DBConnection::class)) {
            return \saso\repository\DBConnection::getPdo();
        }

        $config = \saso\ConfigLoader::load();
        $db     = $config['database'];

        return new PDO(
            (string) ($db['dsn'] ?? ''),
            (string) ($db['user'] ?? ''),
            (string) ($db['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        );
    }

    /**
     * Resolves the JWT signing secret.
     *
     * Resolution order (highest first):
     *   1. JWT_SECRET environment variable (raw string, ≥ 32 chars)
     *   2. APP_KEY environment variable used as HMAC key input
     *   3. Derived from the database DSN (development fallback only)
     */
    private static function jwtSecret(): string
    {
        $jwtSecret = getenv('JWT_SECRET');
        if (is_string($jwtSecret) && strlen($jwtSecret) >= 32) {
            return $jwtSecret;
        }

        $appKey = getenv('APP_KEY');
        if (is_string($appKey) && $appKey !== '') {
            return hash('sha256', $appKey, binary: true);
        }

        $config = \saso\ConfigLoader::load();
        $dsn    = (string) ($config['database']['dsn'] ?? 'saso-fallback');

        return hash('sha256', 'saso-jwt-'.$dsn, binary: true);
    }
}
