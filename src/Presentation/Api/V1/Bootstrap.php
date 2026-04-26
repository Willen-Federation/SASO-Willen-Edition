<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Infrastructure\Translation\TranslatorFactory;
use Saso\Infrastructure\Translation\TranslatorRegistry;
use Saso\Presentation\Api\V1\Controller\HealthController;
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

        return [
            'getHealth'       => [$health, 'handle'],
            'getOpenApiSpec'  => [$openApi, 'yaml'],
            'getSwaggerUi'    => [$swaggerUi, 'page'],
        ];
    }

    public static function specPath(): string
    {
        return dirname(__DIR__, 4).'/config/openapi.yaml';
    }
}
