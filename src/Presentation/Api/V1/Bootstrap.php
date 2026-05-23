<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use PDO;
use Saso\Application\Common\IdempotencyService;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;
use Saso\Infrastructure\Barcode\PdoBarcodeRepository;
use Saso\Infrastructure\Category\PdoCategoryRepository;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Infrastructure\Messaging\NullMessageBus;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use Saso\Infrastructure\MobileConnect\PdoPairingCodeRepository;
use Saso\Infrastructure\MobileConnect\QrCodeRenderer;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;
use Saso\Infrastructure\Translation\TranslatorFactory;
use Saso\Infrastructure\Translation\TranslatorRegistry;
use Saso\Presentation\Api\V1\Controller\Barcode\BarcodeGetController;
use Saso\Presentation\Api\V1\Controller\Category\ListCategoriesController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagCreateController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagDeleteController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagGetController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagListController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagUpdateController;
use Saso\Presentation\Api\V1\Controller\HealthController;
use Saso\Presentation\Api\V1\Controller\Item\AutoRegisterController;
use Saso\Presentation\Api\V1\Controller\Item\CreateItemController;
use Saso\Presentation\Api\V1\Controller\Item\DraftCreateController;
use Saso\Presentation\Api\V1\Controller\Item\GetItemController;
use Saso\Presentation\Api\V1\Controller\Item\ListItemsController;
use Saso\Presentation\Api\V1\Controller\Item\UpdateItemController;
use Saso\Presentation\Api\V1\Controller\Mobile\ConfigBundleController;
use Saso\Presentation\Api\V1\Controller\Mobile\ConnectController;
use Saso\Presentation\Api\V1\Controller\Mobile\DiscoveryController;
use Saso\Presentation\Api\V1\Controller\Mobile\QrController;
use Saso\Presentation\Api\V1\Controller\Mobile\TokenListController;
use Saso\Presentation\Api\V1\Controller\Mobile\TokenRefreshController;
use Saso\Presentation\Api\V1\Controller\Mobile\TokenRevokeController;
use Saso\Presentation\Api\V1\Controller\OpenApiController;
use Saso\Presentation\Api\V1\Controller\ReadinessController;
use Saso\Presentation\Api\V1\Controller\StorageLocation\GetStorageLocationController;
use Saso\Presentation\Api\V1\Controller\StorageLocation\ListStorageLocationsController;
use Saso\Presentation\Api\V1\Controller\StorageLocation\StorageLocationItemsController;
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

        // Boot-time exceptions (missing APP_KEY, malformed openapi.yaml,
        // controllers that fail their own construction) used to escape
        // the router and bubble up as PHP's default 500 page. Catch them
        // here so they render as RFC 7807 Problem responses — operators
        // get the same JSON envelope (with traceId) they get from any
        // other API failure, instead of an HTML stack trace.
        try {
            $spec     = OpenApiSpec::load(self::specPath());
            $handlers = self::handlerMap($spec);
            $router   = new Router($spec, $handlers, $exceptionHandler);
        } catch (\Throwable $e) {
            $exceptionHandler->handle($e, $request->path, $locale);
            return;
        }

        $router->dispatch($request, $locale);
    }

    /**
     * @return array<string, callable(HttpRequest): \Saso\Presentation\Api\V1\Response\HttpResponse>
     */
    private static function handlerMap(OpenApiSpec $spec): array
    {
        $health    = new HealthController();
        $readiness = new ReadinessController(static fn (): PDO => self::createPdo());
        $openApi   = new OpenApiController($spec);
        $swaggerUi = new SwaggerUiController();

        $pdo = self::createPdo();
        $jwt = new JwtService(self::jwtSecret());

        // Shared guard and idempotency service used by authenticated endpoints.
        $jwtGuard    = new JwtGuard($jwt);
        $idempotency = new IdempotencyService($pdo);

        $barcodeRepo = new PdoBarcodeRepository($pdo);
        $barcodeGet  = new BarcodeGetController($barcodeRepo);

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
        $configBundle = new ConfigBundleController($flagRepo, $jwtGuard);
        $tokenList    = new TokenListController($tokenRepo);
        $tokenRevoke  = new TokenRevokeController($tokenRepo);
        $tokenRefresh = new TokenRefreshController($tokenRepo, $jwt);

        // Auth provider discovery (public).
        $config       = \saso\ConfigLoader::load();
        $providerRepo = new PdoAuthProviderRepository($pdo, new SecretEncryptor(self::encryptorKey()));
        $discovery    = new DiscoveryController(
            providers: $providerRepo,
            serverName: (string) ($config['site']['name'] ?? 'SASO'),
            version: '1.0.0-alpha',
        );

        // Item controllers.
        $listItems   = new ListItemsController($pdo, $jwtGuard);
        $getItem     = new GetItemController($pdo, $jwtGuard);
        $createItem  = new CreateItemController($pdo, $jwtGuard, $idempotency);
        $updateItem  = new UpdateItemController($pdo, $jwtGuard, $idempotency);
        $createDraft  = new DraftCreateController($pdo, new NullMessageBus(), $jwt);
        $autoRegister = new AutoRegisterController($pdo, new NullMessageBus(), $jwt);

        // Category controller.
        $catRepo  = new PdoCategoryRepository($pdo);
        $listCats = new ListCategoriesController($catRepo, $jwtGuard);

        // Storage location controllers.
        $locRepo  = new PdoStorageLocationRepository($pdo);
        $listLocs = new ListStorageLocationsController($locRepo, $jwtGuard);
        $getLoc   = new GetStorageLocationController($locRepo, $jwtGuard);
        $locItems = new StorageLocationItemsController($pdo, $jwtGuard);

        return [
            'getHealth'       => [$health, 'handle'],
            'getReadiness'    => [$readiness, 'handle'],
            'getOpenApiSpec'  => [$openApi, 'yaml'],
            'getSwaggerUi'    => [$swaggerUi, 'page'],

            'listAuthProviders' => [$discovery, 'handle'],

            'getBarcode'        => [$barcodeGet, 'handle'],

            'listFeatureFlags'  => [$flagList, 'handle'],
            'createFeatureFlag' => static function (HttpRequest $r) use ($flagCreate) {
                self::requireSessionAuth();
                return $flagCreate->handle($r);
            },
            'getFeatureFlag'    => [$flagGet, 'handle'],
            'updateFeatureFlag' => static function (HttpRequest $r) use ($flagUpdate) {
                self::requireSessionAuth();
                return $flagUpdate->handle($r);
            },
            'deleteFeatureFlag' => static function (HttpRequest $r) use ($flagDelete) {
                self::requireSessionAuth();
                return $flagDelete->handle($r);
            },

            'createPairingCode'  => static function (HttpRequest $r) use ($qr) {
                self::requireSessionAuth();
                return $qr->handle($r);
            },
            'mobileConnect'      => [$connect, 'handle'],
            'refreshMobileToken' => [$tokenRefresh, 'handle'],
            'getMobileConfig'    => [$configBundle, 'handle'],
            'listDeviceTokens'   => static function (HttpRequest $r) use ($tokenList) {
                self::requireSessionAuth();
                return $tokenList->handle($r);
            },
            'revokeDeviceToken'  => static function (HttpRequest $r) use ($tokenRevoke) {
                self::requireSessionAuth();
                return $tokenRevoke->handle($r);
            },

            'listItems'               => [$listItems, 'handle'],
            'getItem'                 => [$getItem, 'handle'],
            'createItem'              => [$createItem, 'handle'],
            'updateItem'              => [$updateItem, 'handle'],
            'createItemDraft'         => [$createDraft, 'handle'],
            'autoRegisterItem'        => [$autoRegister, 'handle'],
            'listCategories'          => [$listCats, 'handle'],
            'listStorageLocations'    => [$listLocs, 'handle'],
            'getStorageLocation'      => [$getLoc, 'handle'],
            'listStorageLocationItems' => [$locItems, 'handle'],
        ];
    }

    public static function specPath(): string
    {
        return dirname(__DIR__, 4).'/config/openapi.yaml';
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
     *   2. APP_KEY environment variable (≥ 32 chars) run through SHA-256
     *
     * Boots fail closed if neither is set to a value of at least 32 bytes.
     */
    private static function jwtSecret(): string
    {
        $jwtSecret = getenv('JWT_SECRET');
        if (is_string($jwtSecret) && strlen($jwtSecret) >= 32) {
            return $jwtSecret;
        }

        $appKey = getenv('APP_KEY');
        if (is_string($appKey) && strlen($appKey) >= 32) {
            return hash('sha256', $appKey, binary: true);
        }

        throw new \RuntimeException(
            'JWT_SECRET (or APP_KEY) must be set to a value of at least 32 bytes. '
            .'Refusing to boot with an insecure fallback. See .env.example.'
        );
    }

    /**
     * Derives the AES-256-GCM key used by {@see SecretEncryptor} from APP_KEY.
     *
     * Resolution order:
     *   1. APP_KEY as base64-encoded 32 bytes (44 chars with padding)
     *   2. APP_KEY as hex-encoded 32 bytes (64 hex chars)
     *   3. APP_KEY as any string ≥ 32 chars, run through SHA-256
     *
     * Boots fail closed if APP_KEY is missing or shorter than 32 characters.
     */
    private static function encryptorKey(): string
    {
        $appKey = getenv('APP_KEY');
        if (is_string($appKey) && $appKey !== '') {
            $raw = base64_decode($appKey, strict: true);
            if ($raw !== false && strlen($raw) === 32) {
                return $raw;
            }

            if (preg_match('/^[0-9a-fA-F]{64}$/', $appKey)) {
                $hex = hex2bin($appKey);
                if ($hex !== false && strlen($hex) === 32) {
                    return $hex;
                }
            }

            if (strlen($appKey) >= 32) {
                return hash('sha256', $appKey, binary: true);
            }
        }

        throw new \RuntimeException(
            'APP_KEY must be set to a base64-encoded 32 bytes, hex-encoded 32 bytes, '
            .'or any string of at least 32 characters. Refusing to boot with an all-zero AES key. '
            .'See .env.example.'
        );
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
}
