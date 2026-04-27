<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Mcp;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Mcp\Exception\MalformedMcpRequestException;
use Saso\Domain\Mcp\Exception\McpScopeInsufficientException;
use Saso\Domain\Mcp\Exception\UnknownMcpToolException;
use Saso\Domain\Shared\ErrorCode;

final class McpExceptionTest extends TestCase
{
    public function testUnknownMcpToolCarriesCorrectCode(): void
    {
        $ex = new UnknownMcpToolException('search_items');

        self::assertSame(ErrorCode::McpUnknownTool, $ex->errorCode());
        self::assertStringContainsString('search_items', $ex->getMessage());
        self::assertSame(['tool' => 'search_items'], $ex->context());
    }

    public function testMalformedMcpRequestDefaultMessage(): void
    {
        $ex = new MalformedMcpRequestException();

        self::assertSame(ErrorCode::McpMalformedRequest, $ex->errorCode());
        self::assertNotEmpty($ex->getMessage());
    }

    public function testMalformedMcpRequestCustomReason(): void
    {
        $ex = new MalformedMcpRequestException('missing jsonrpc field');

        self::assertSame('missing jsonrpc field', $ex->getMessage());
    }

    public function testMcpScopeInsufficientCarriesScope(): void
    {
        $ex = new McpScopeInsufficientException('items:write');

        self::assertSame(ErrorCode::McpScopeInsufficient, $ex->errorCode());
        self::assertStringContainsString('items:write', $ex->getMessage());
        self::assertSame(['requiredScope' => 'items:write'], $ex->context());
    }
}
