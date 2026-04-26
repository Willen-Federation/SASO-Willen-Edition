<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\Controller\SwaggerUiController;
use Saso\Presentation\Api\V1\HttpRequest;

final class SwaggerUiControllerTest extends TestCase
{
    public function testReturnsHtmlPointingAtTheLocalSpec(): void
    {
        $controller = new SwaggerUiController();

        $response = $controller->page(new HttpRequest('GET', '/api/v1/docs'));

        self::assertSame(200, $response->status);
        self::assertStringStartsWith('text/html', $response->contentType);
        self::assertStringContainsString('<div id="swagger-ui">', $response->body);
        self::assertStringContainsString("url: '/api/v1/openapi.yaml'", $response->body);
    }
}
