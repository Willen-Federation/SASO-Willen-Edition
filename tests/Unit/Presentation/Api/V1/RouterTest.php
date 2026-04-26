<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1;

use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\OpenApiSpec;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Saso\Presentation\Api\V1\Router;
use Saso\Presentation\Http\Problem\ProblemExceptionHandler;
use Saso\Presentation\Http\Problem\ProblemRenderer;

final class RouterTest extends TestCase
{
    private const TWO_ROUTE_SPEC = <<<'YAML'
        openapi: 3.1.0
        info:
          title: t
          version: 0
        paths:
          /api/v1/health:
            get:
              operationId: getHealth
              responses:
                '200':
                  description: ok
          /api/v1/items/{id}:
            get:
              operationId: getItem
              responses:
                '200':
                  description: ok
            delete:
              operationId: deleteItem
              responses:
                '204':
                  description: ok
        YAML;

    public function testDispatchesFoundRouteToHandler(): void
    {
        $spec     = OpenApiSpec::fromYamlString(self::TWO_ROUTE_SPEC);
        $captured = null;

        $router = new Router(
            spec: $spec,
            handlers: [
                'getHealth' => static function (HttpRequest $req) use (&$captured): JsonResponse {
                    $captured = $req;

                    return new JsonResponse(200, ['status' => 'ok']);
                },
                'getItem'    => static fn () => new JsonResponse(200, ['id' => null]),
                'deleteItem' => static fn () => new JsonResponse(204, []),
            ],
            exceptionHandler: $this->makeHandler(),
        );

        ob_start();
        $router->dispatch(new HttpRequest('GET', '/api/v1/health'));
        $output = ob_get_clean();

        self::assertNotNull($captured);
        self::assertSame('GET', $captured->method);
        self::assertSame('{"status":"ok"}', $output);
    }

    public function testCapturesPathParameters(): void
    {
        $spec     = OpenApiSpec::fromYamlString(self::TWO_ROUTE_SPEC);
        $captured = null;

        $router = new Router(
            spec: $spec,
            handlers: [
                'getHealth' => static fn () => new JsonResponse(200, []),
                'getItem'   => static function (HttpRequest $req) use (&$captured): JsonResponse {
                    $captured = $req;

                    return new JsonResponse(200, ['id' => $req->pathParams['id'] ?? null]);
                },
                'deleteItem' => static fn () => new JsonResponse(204, []),
            ],
            exceptionHandler: $this->makeHandler(),
        );

        ob_start();
        $router->dispatch(new HttpRequest('GET', '/api/v1/items/4711'));
        $output = ob_get_clean();

        self::assertSame('4711', $captured?->pathParams['id'] ?? null);
        self::assertSame('{"id":"4711"}', $output);
    }

    public function testNotFoundProducesProblemDetailsWithCode9003(): void
    {
        $spec   = OpenApiSpec::fromYamlString(self::TWO_ROUTE_SPEC);
        $router = new Router(
            spec: $spec,
            handlers: [
                'getHealth'  => static fn () => new JsonResponse(200, []),
                'getItem'    => static fn () => new JsonResponse(200, []),
                'deleteItem' => static fn () => new JsonResponse(204, []),
            ],
            exceptionHandler: $this->makeHandler(),
        );

        ob_start();
        $router->dispatch(new HttpRequest('GET', '/api/v1/unknown'));
        $output = ob_get_clean();

        $decoded = json_decode($output, associative: true);
        self::assertIsArray($decoded);
        self::assertSame('SASO-INFRA-9003', $decoded['code']);
        self::assertSame(404, $decoded['status']);
    }

    public function testMethodNotAllowedProducesProblemDetailsWithCode9004(): void
    {
        $spec   = OpenApiSpec::fromYamlString(self::TWO_ROUTE_SPEC);
        $router = new Router(
            spec: $spec,
            handlers: [
                'getHealth'  => static fn () => new JsonResponse(200, []),
                'getItem'    => static fn () => new JsonResponse(200, []),
                'deleteItem' => static fn () => new JsonResponse(204, []),
            ],
            exceptionHandler: $this->makeHandler(),
        );

        ob_start();
        $router->dispatch(new HttpRequest('POST', '/api/v1/health'));
        $output = ob_get_clean();

        $decoded = json_decode($output, associative: true);
        self::assertIsArray($decoded);
        self::assertSame('SASO-INFRA-9004', $decoded['code']);
        self::assertSame(405, $decoded['status']);
    }

    public function testHandlerExceptionIsForwardedToProblemHandler(): void
    {
        $spec   = OpenApiSpec::fromYamlString(self::TWO_ROUTE_SPEC);
        $router = new Router(
            spec: $spec,
            handlers: [
                'getHealth' => static function () {
                    throw new RuntimeException('boom');
                },
                'getItem'    => static fn () => new JsonResponse(200, []),
                'deleteItem' => static fn () => new JsonResponse(204, []),
            ],
            exceptionHandler: $this->makeHandler(),
        );

        ob_start();
        $router->dispatch(new HttpRequest('GET', '/api/v1/health'));
        $output = ob_get_clean();

        $decoded = json_decode($output, associative: true);
        self::assertIsArray($decoded);
        self::assertSame('SASO-INFRA-9000', $decoded['code']);
        self::assertSame(500, $decoded['status']);
    }

    public function testConstructorRejectsSpecWithUnregisteredOperationId(): void
    {
        $spec = OpenApiSpec::fromYamlString(self::TWO_ROUTE_SPEC);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No handler registered for operationId "getItem"');

        new Router(
            spec: $spec,
            handlers: [
                'getHealth'  => static fn () => new JsonResponse(200, []),
                'deleteItem' => static fn () => new JsonResponse(204, []),
            ],
            exceptionHandler: $this->makeHandler(),
        );
    }

    private function makeHandler(): ProblemExceptionHandler
    {
        return new ProblemExceptionHandler(
            logger: MonologFactory::withHandler(new TestHandler()),
            renderer: new ProblemRenderer(),
        );
    }
}
