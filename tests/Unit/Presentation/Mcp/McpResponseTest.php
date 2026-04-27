<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Mcp\McpResponse;

final class McpResponseTest extends TestCase
{
    public function testResultContainsResultKey(): void
    {
        $response = McpResponse::result(1, ['status' => 'ok']);
        $arr      = $response->toArray();

        self::assertSame('2.0', $arr['jsonrpc']);
        self::assertSame(1, $arr['id']);
        self::assertArrayHasKey('result', $arr);
        self::assertArrayNotHasKey('error', $arr);
        self::assertSame(200, $response->httpStatus);
    }

    public function testParseErrorHas400Status(): void
    {
        $response = McpResponse::parseError();

        self::assertSame(400, $response->httpStatus);
        $arr = $response->toArray();
        self::assertSame(-32700, $arr['error']['code']);
        self::assertNull($arr['id']);
    }

    public function testInvalidRequestHas32600Code(): void
    {
        $response = McpResponse::invalidRequest(2);
        $arr      = $response->toArray();

        self::assertSame(-32600, $arr['error']['code']);
        self::assertSame(2, $arr['id']);
    }

    public function testMethodNotFoundContainsMethodName(): void
    {
        $response = McpResponse::methodNotFound('x', 'unknown/method');
        $arr      = $response->toArray();

        self::assertSame(-32601, $arr['error']['code']);
        self::assertStringContainsString('unknown/method', $arr['error']['message']);
    }

    public function testInvalidParamsHas32602Code(): void
    {
        $response = McpResponse::invalidParams(3, 'missing name');
        $arr      = $response->toArray();

        self::assertSame(-32602, $arr['error']['code']);
        self::assertStringContainsString('missing name', $arr['error']['message']);
    }

    public function testUnauthorizedHas401Status(): void
    {
        $response = McpResponse::unauthorized(4);

        self::assertSame(401, $response->httpStatus);
        self::assertSame(-32001, $response->toArray()['error']['code']);
    }

    public function testToolNotFoundHas32002Code(): void
    {
        $response = McpResponse::toolNotFound(5, 'my_tool');
        $arr      = $response->toArray();

        self::assertSame(-32002, $arr['error']['code']);
        self::assertStringContainsString('my_tool', $arr['error']['message']);
    }

    public function testInternalErrorHas32603Code(): void
    {
        $response = McpResponse::internalError(6, 'DB down');
        $arr      = $response->toArray();

        self::assertSame(-32603, $arr['error']['code']);
        self::assertStringContainsString('DB down', $arr['error']['message']);
    }
}
