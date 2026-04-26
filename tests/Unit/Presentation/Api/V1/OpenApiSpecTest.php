<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Presentation\Api\V1\OpenApiSpec;

final class OpenApiSpecTest extends TestCase
{
    public function testFromYamlStringExtractsRoutes(): void
    {
        $yaml = <<<'YAML'
            openapi: 3.1.0
            info:
              title: t
              version: 0
            paths:
              /health:
                get:
                  operationId: getHealth
                  responses:
                    '200':
                      description: ok
              /items/{id}:
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

        $spec = OpenApiSpec::fromYamlString($yaml);

        $routes = $spec->routes();
        self::assertCount(3, $routes);

        $signatures = array_map(
            static fn ($r) => sprintf('%s %s -> %s', $r->method, $r->path, $r->operationId),
            $routes,
        );
        self::assertContains('GET /health -> getHealth', $signatures);
        self::assertContains('GET /items/{id} -> getItem', $signatures);
        self::assertContains('DELETE /items/{id} -> deleteItem', $signatures);
    }

    public function testRawYamlIsPreserved(): void
    {
        $yaml = "openapi: 3.1.0\ninfo:\n  title: t\n  version: 0\npaths: {}\n";

        $spec = OpenApiSpec::fromYamlString($yaml);

        self::assertSame($yaml, $spec->rawYaml());
    }

    public function testThrowsWhenOperationIdIsMissing(): void
    {
        $yaml = <<<'YAML'
            openapi: 3.1.0
            info:
              title: t
              version: 0
            paths:
              /health:
                get:
                  responses:
                    '200':
                      description: ok
            YAML;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must declare an operationId');

        OpenApiSpec::fromYamlString($yaml);
    }

    public function testIgnoresNonHttpKeysOnPathItems(): void
    {
        // OpenAPI lets `parameters` and `summary` sit alongside HTTP
        // verbs at the path level; the loader must skip them.
        $yaml = <<<'YAML'
            openapi: 3.1.0
            info:
              title: t
              version: 0
            paths:
              /items:
                summary: Items collection
                parameters: []
                get:
                  operationId: listItems
                  responses:
                    '200':
                      description: ok
            YAML;

        $spec = OpenApiSpec::fromYamlString($yaml);

        self::assertCount(1, $spec->routes());
        self::assertSame('listItems', $spec->routes()[0]->operationId);
    }

    public function testLoadThrowsWhenFileMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAPI specification not found');

        OpenApiSpec::load('/nonexistent/openapi.yaml');
    }

    public function testLoadsTheCommittedProjectSpec(): void
    {
        $spec = OpenApiSpec::load(dirname(__DIR__, 5).'/config/openapi.yaml');

        $signatures = array_map(
            static fn ($r) => sprintf('%s %s', $r->method, $r->path),
            $spec->routes(),
        );

        self::assertContains('GET /health', $signatures);
        self::assertContains('GET /openapi.yaml', $signatures);
        self::assertContains('GET /docs', $signatures);
    }
}
