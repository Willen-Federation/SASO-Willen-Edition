<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\Controller\HealthController;
use Saso\Presentation\Api\V1\HttpRequest;

final class HealthControllerTest extends TestCase
{
    public function testReturns200WithStatusOk(): void
    {
        $controller = new HealthController(
            new DateTimeImmutable('2026-04-26T12:34:56Z', new DateTimeZone('UTC')),
        );

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health'));

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body['status']);
        self::assertSame(HealthController::VERSION, $response->body['version']);
        self::assertSame('2026-04-26T12:34:56+00:00', $response->body['time']);
    }

    public function testEncodesAsCompactJson(): void
    {
        $controller = new HealthController();

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health'));

        $decoded = json_decode($response->encode(), associative: true);
        self::assertIsArray($decoded);
        self::assertSame('ok', $decoded['status']);
        self::assertArrayHasKey('time', $decoded);
    }
}
