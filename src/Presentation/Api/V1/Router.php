<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

use RuntimeException;
use Saso\Domain\Shared\Exception\MethodNotAllowedException;
use Saso\Domain\Shared\Exception\RouteNotFoundException;
use Saso\Presentation\Api\V1\Response\HttpResponse;
use Saso\Presentation\Http\Problem\ProblemExceptionHandler;

use Throwable;

/**
 * Schema-first router for the `/api/v1/*` surface.
 *
 * The dispatch table is built from {@see OpenApiSpec::routes()}; each
 * route's `operationId` keys into a PHP handler map registered at
 * construction. Boot-time validation refuses to start if the spec
 * declares an operation the handler map does not implement — no schema /
 * code drift in either direction.
 *
 * Errors raised from a handler are forwarded to {@see ProblemExceptionHandler},
 * so every API failure renders as RFC 7807 Problem Details with a `code`,
 * `traceId`, and the configured `instance`.
 */
final class Router
{
    private readonly Dispatcher $dispatcher;

    /**
     * @param array<string, callable(HttpRequest): HttpResponse> $handlers operationId → handler
     */
    public function __construct(
        private readonly OpenApiSpec $spec,
        private readonly array $handlers,
        private readonly ProblemExceptionHandler $exceptionHandler,
    ) {
        $this->verifyHandlerCoverage();
        $this->dispatcher = $this->buildDispatcher();
    }

    public function dispatch(HttpRequest $request, ?string $locale = null): void
    {
        try {
            // The OpenAPI spec declares paths without the /api/v1 server-prefix
            // (e.g. `/health`, `/items`). Strip that prefix from the incoming
            // request path before handing off to FastRoute so the two match.
            $dispatchPath = preg_replace('#^/api/v1(?=/|$)#', '', $request->path) ?: '/';
            $info         = $this->dispatcher->dispatch($request->method, $dispatchPath);

            switch ($info[0]) {
                case Dispatcher::NOT_FOUND:
                    throw RouteNotFoundException::for($request->method, $request->path);

                case Dispatcher::METHOD_NOT_ALLOWED:
                    /** @var list<string> $allowed */
                    $allowed = $info[1] ?? [];

                    throw MethodNotAllowedException::for($request->method, $request->path, $allowed);

                case Dispatcher::FOUND:
                    /** @var string $operationId */
                    $operationId = $info[1];
                    /** @var array<string, string> $vars */
                    $vars = $info[2] ?? [];

                    $resolvedRequest = new HttpRequest(
                        method: $request->method,
                        path: $request->path,
                        headers: $request->headers,
                        query: $request->query,
                        pathParams: $vars,
                        body: $request->body,
                    );

                    /** @var callable(HttpRequest): HttpResponse $handler */
                    $handler  = $this->handlers[$operationId];
                    $response = $handler($resolvedRequest);
                    $response->emit();

                    return;
            }
        } catch (Throwable $e) {
            $this->exceptionHandler->handle($e, $request->path, $locale);
        }
    }

    private function verifyHandlerCoverage(): void
    {
        foreach ($this->spec->routes() as $route) {
            if (!array_key_exists($route->operationId, $this->handlers)) {
                throw new RuntimeException(sprintf(
                    'No handler registered for operationId "%s" (%s %s).',
                    $route->operationId,
                    $route->method,
                    $route->path,
                ));
            }
        }
    }

    private function buildDispatcher(): Dispatcher
    {
        $routes = $this->spec->routes();

        return simpleDispatcher(static function (RouteCollector $r) use ($routes): void {
            foreach ($routes as $route) {
                $r->addRoute($route->method, $route->path, $route->operationId);
            }
        });
    }
}
