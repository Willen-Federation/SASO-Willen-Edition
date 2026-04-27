<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use PDO;
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
 *
 * No DI container yet; M3 ships with a hand-written composition root
 * because the wiring is small enough to fit on screen and explicit
 * dependencies make the boundaries between layers easy to audit.
 */
final class Bootstrap
{
    /**
     * Entry point: dispatch the current request to the API router. The
     * legacy front controller calls this when `REQUEST_URI` starts with
     * `/api/v1/`; everything else falls through to the legacy router.
     */
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

        $flagRepo   = new PdoFeatureFlagRepository($pdo);
        $codeRepo   = new PdoPairingCodeRepository($pdo);
        $tokenRepo  = new PdoDeviceTokenRepository($pdo);
        $qrRenderer = new QrCodeRenderer();

        $flagList   = new FeatureFlagListController($flagRepo);
        $flagCreate = new FeatureFlagCreateController($flagRepo);
        $flagGet    = new FeatureFlagGetController($flagRepo);
        $flagUpdate = new FeatureFlagUpdateController($flagRepo);
        $flagDelete = new FeatureFlagDeleteController($flagRepo);

        $qr          = new QrController($codeRepo, $qrRenderer);
        $connect     = new ConnectController($codeRepo, $tokenRepo);
        $configBundle = new ConfigBundleController($flagRepo);
        $tokenList   = new TokenListController($tokenRepo);
        $tokenRevoke = new TokenRevokeController($tokenRepo);

        return [
            'getHealth'       => [$health, 'handle'],
            'getOpenApiSpec'  => [$openApi, 'yaml'],
            'getSwaggerUi'    => [$swaggerUi, 'page'],

            'listFeatureFlags'  => [$flagList, 'handle'],
            'createFeatureFlag' => [$flagCreate, 'handle'],
            'getFeatureFlag'    => [$flagGet, 'handle'],
            'updateFeatureFlag' => [$flagUpdate, 'handle'],
            'deleteFeatureFlag' => [$flagDelete, 'handle'],

            'createPairingCode' => [$qr, 'handle'],
            'mobileConnect'     => [$connect, 'handle'],
            'getMobileConfig'   => [$configBundle, 'handle'],
            'listDeviceTokens'  => [$tokenList, 'handle'],
            'revokeDeviceToken' => [$tokenRevoke, 'handle'],
        ];
    }

    public static function specPath(): string
    {
        return dirname(__DIR__, 4).'/config/openapi.yaml';
    }

    private static function createPdo(): PDO
    {
        // Reuse the legacy singleton when available; fall back to a fresh
        // connection from config.json so tests can inject their own DSN.
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
}
