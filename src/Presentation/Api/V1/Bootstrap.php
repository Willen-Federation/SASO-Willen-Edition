<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use PDO;
use Saso\Application\Common\IdempotencyService;
use Saso\Application\Enrichment\EnrichmentPipeline;
use Saso\Application\Enrichment\Step\AiVisionStep;
use Saso\Application\Enrichment\Step\IsbnLookupStep;
use Saso\Application\Enrichment\Step\JanLookupStep;
use Saso\Application\Enrichment\Step\MergeStep;
use Saso\Application\Messaging\Handler\ProcessItemDraftHandler;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Infrastructure\Ai\AiAssistantFactory;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;
use Saso\Infrastructure\Barcode\PdoBarcodeRepository;
use Saso\Infrastructure\Category\PdoCategoryRepository;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use Saso\Infrastructure\ItemDraft\PdoItemDraftRepository;
use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Infrastructure\Messaging\MessageBusFactory;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use Saso\Infrastructure\MobileConnect\PdoPairingCodeRepository;
use Saso\Infrastructure\MobileConnect\QrCodeRenderer;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;
use Saso\Infrastructure\Translation\TranslatorFactory;
use Saso\Infrastructure\Translation\TranslatorRegistry;
use Saso\Presentation\Api\V1\Controller\Auth\ProviderGetController;
use Saso\Presentation\Api\V1\Controller\Auth\ProviderListController;
use Saso\Presentation\Api\V1\Controller\Auth\ProviderTestController;
use Saso\Presentation\Api\V1\Controller\Barcode\BarcodeGetController;
use Saso\Presentation\Api\V1\Controller\Category\ListCategoriesController;
use Saso\Presentation\Api\V1\Controller\Config\FieldsController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagCreateController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagDeleteController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagGetController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagListController;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagUpdateController;
use Saso\Presentation\Api\V1\Controller\HealthController;
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

        $spec     = OpenApiSpec::load(self::specPath());
        $handlers = self::handlerMap($spec, $logger);

        $router = new Router($spec, $handlers, $exceptionHandler);
        $router->dispatch($request, $locale);
    }

    /**
     * @return array<string, callable(HttpRequest): \Saso\Presentation\Api\V1\Response\HttpResponse>
     */
    private static function handlerMap(OpenApiSpec $spec, \Psr\Log\LoggerInterface $logger): array
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

        // Shared encryption key (APP_KEY → 32 bytes)
        $encryptor = new SecretEncryptor(self::encryptionKey());

        // Auth provider controllers
        $authProviders    = new PdoAuthProviderRepository($pdo, $encryptor);
        $providerList     = new ProviderListController($authProviders);
        $providerGet      = new ProviderGetController($authProviders);
        $providerTest     = new ProviderTestController($authProviders);

        // Discovery
        $serverName = (string) (getenv('APP_NAME') ?: 'SASO');
        $version    = (string) (getenv('APP_VERSION') ?: '1.0.0');
        $discovery  = new DiscoveryController($authProviders, $serverName, $version);

        // Item draft (createItemDraft) — full enrichment pipeline
        $settings   = new PdoSystemSettingService($pdo, $encryptor);
        $ai         = AiAssistantFactory::forVision($settings);
        $pipeline   = new EnrichmentPipeline(
            new IsbnLookupStep(),
            new JanLookupStep(),
            new AiVisionStep($ai),
            new MergeStep(),
        );
        $draftRepo    = new PdoItemDraftRepository($pdo);
        $draftHandler = new ProcessItemDraftHandler($draftRepo, $pipeline, $logger);
        $bus          = MessageBusFactory::create([ProcessItemDraft::class => [$draftHandler]]);
        $draftCreate  = new DraftCreateController($pdo, $bus, $jwt);

        // Config fields + barcode
        $fields  = new FieldsController($pdo);
        $barcode = new BarcodeGetController(new PdoBarcodeRepository($pdo));

        // Item / Category / StorageLocation endpoints (JWT required)
        $guard       = new JwtGuard($jwt);
        $idempotency = new IdempotencyService($pdo);
        $categories  = new PdoCategoryRepository($pdo);
        $locations   = new PdoStorageLocationRepository($pdo);

        $listItems   = new ListItemsController($pdo, $guard);
        $getItem     = new GetItemController($pdo, $guard);
        $createItem  = new CreateItemController($pdo, $guard, $idempotency);
        $updateItem  = new UpdateItemController($pdo, $guard, $idempotency);

        $listCategories = new ListCategoriesController($categories, $guard);

        $listLocations  = new ListStorageLocationsController($locations, $guard);
        $getLocation    = new GetStorageLocationController($locations, $guard);
        $locationItems  = new StorageLocationItemsController($pdo, $guard);

        return [
            'getHealth'       => [$health, 'handle'],
            'getOpenApiSpec'  => [$openApi, 'yaml'],
            'getSwaggerUi'    => [$swaggerUi, 'page'],

            'listAuthProviders' => [$providerList, 'handle'],
            'getAuthProvider'   => [$providerGet, 'handle'],
            'testAuthProvider'  => [$providerTest, 'handle'],

            'getMobileDiscovery' => [$discovery, 'handle'],

            'listFeatureFlags'  => [$flagList, 'handle'],
            'createFeatureFlag' => [$flagCreate, 'handle'],
            'getFeatureFlag'    => [$flagGet, 'handle'],
            'updateFeatureFlag' => [$flagUpdate, 'handle'],
            'deleteFeatureFlag' => [$flagDelete, 'handle'],

            'createPairingCode'  => [$qr, 'handle'],
            'mobileConnect'      => [$connect, 'handle'],
            'refreshMobileToken' => [$tokenRefresh, 'handle'],
            'getMobileConfig'    => [$configBundle, 'handle'],
            'listDeviceTokens'   => [$tokenList, 'handle'],
            'revokeDeviceToken'  => [$tokenRevoke, 'handle'],

            'createItemDraft' => [$draftCreate, 'handle'],

            'getConfigFields' => [$fields, 'handle'],
            'getBarcode'      => [$barcode, 'handle'],

            'listItems'   => [$listItems, 'handle'],
            'getItem'     => [$getItem, 'handle'],
            'createItem'  => [$createItem, 'handle'],
            'updateItem'  => [$updateItem, 'handle'],

            'listCategories' => [$listCategories, 'handle'],

            'listStorageLocations'     => [$listLocations, 'handle'],
            'getStorageLocation'       => [$getLocation, 'handle'],
            'listStorageLocationItems' => [$locationItems, 'handle'],
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

    /**
     * Derives a 32-byte AES-256 key from APP_KEY for at-rest encryption.
     * Uses a domain-separated HMAC so the key is distinct from jwtSecret().
     */
    private static function encryptionKey(): string
    {
        $appKey = getenv('APP_KEY');
        if (is_string($appKey) && $appKey !== '') {
            return hash_hmac('sha256', 'saso-encrypt', $appKey, binary: true);
        }

        $config = \saso\ConfigLoader::load();
        $dsn    = (string) ($config['database']['dsn'] ?? 'saso-fallback');

        return hash('sha256', 'saso-encrypt-'.$dsn, binary: true);
    }
}
