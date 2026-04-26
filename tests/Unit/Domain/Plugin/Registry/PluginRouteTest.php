<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Plugin\Registry;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\Registry\PluginRoute;

final class PluginRouteTest extends TestCase
{
    public function testStoresFields(): void
    {
        $r = new PluginRoute(method: 'POST', path: '/items', operationId: 'createItem');

        self::assertSame('POST', $r->method);
        self::assertSame('/items', $r->path);
        self::assertSame('createItem', $r->operationId);
    }

    public function testMethodUpper(): void
    {
        $r = new PluginRoute(method: 'get', path: '/x', operationId: 'getX');

        self::assertSame('GET', $r->methodUpper());
    }

    public function testRejectsUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginRoute(method: 'CONNECT', path: '/x', operationId: 'op');
    }

    public function testRejectsPathWithoutLeadingSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginRoute(method: 'GET', path: 'items', operationId: 'op');
    }

    public function testRejectsEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginRoute(method: 'GET', path: '', operationId: 'op');
    }

    public function testRejectsEmptyOperationId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginRoute(method: 'GET', path: '/x', operationId: '');
    }
}
