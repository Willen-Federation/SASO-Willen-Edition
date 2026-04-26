<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\Controller\OpenApiController;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\OpenApiSpec;

final class OpenApiControllerTest extends TestCase
{
    public function testServesRawYamlVerbatim(): void
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
            YAML;

        $spec       = OpenApiSpec::fromYamlString($yaml);
        $controller = new OpenApiController($spec);

        $response = $controller->yaml(new HttpRequest('GET', '/api/v1/openapi.yaml'));

        self::assertSame(200, $response->status);
        self::assertSame($yaml, $response->body);
        self::assertStringStartsWith('application/yaml', $response->contentType);
    }
}
