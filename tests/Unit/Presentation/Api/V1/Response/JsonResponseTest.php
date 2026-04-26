<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Response;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\Response\JsonResponse;

final class JsonResponseTest extends TestCase
{
    public function testEncodeProducesCompactUnicodeJson(): void
    {
        $r = new JsonResponse(200, ['greeting' => 'こんにちは', 'path' => '/api/v1/items']);

        $body = $r->encode();

        self::assertJson($body);
        self::assertStringContainsString('こんにちは', $body);
        self::assertStringNotContainsString('\u', $body);
        self::assertStringContainsString('"path":"/api/v1/items"', $body);
        self::assertStringNotContainsString('\/', $body);
    }

    public function testEmitWritesEncodedBody(): void
    {
        $r = new JsonResponse(201, ['ok' => true]);

        ob_start();
        $r->emit();
        $output = ob_get_clean();

        self::assertSame('{"ok":true}', $output);
    }
}
