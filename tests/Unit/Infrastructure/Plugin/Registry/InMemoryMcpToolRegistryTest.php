<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Plugin\Registry;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Infrastructure\Plugin\Registry\InMemoryMcpToolRegistry;

final class InMemoryMcpToolRegistryTest extends TestCase
{
    public function testRegisterCoreThenGet(): void
    {
        $r    = new InMemoryMcpToolRegistry();
        $tool = $this->fakeTool();
        $r->registerCore(new RegistryName('search_items'), $tool);

        self::assertTrue($r->has(new RegistryName('search_items')));
        self::assertSame($tool, $r->get(new RegistryName('search_items')));
    }

    public function testPluginCannotDisplaceReservedName(): void
    {
        $r = new InMemoryMcpToolRegistry();
        $r->registerCore(new RegistryName('search_items'), $this->fakeTool());

        $this->expectException(RegistryCollisionException::class);

        $r->register(new RegistryName('search_items'), $this->fakeTool());
    }

    public function testPluginCanRegisterVendorTool(): void
    {
        $r = new InMemoryMcpToolRegistry();
        $r->register(new RegistryName('acme:custom_report'), $this->fakeTool());

        self::assertTrue($r->has(new RegistryName('acme:custom_report')));
    }

    public function testNamesIsExactList(): void
    {
        $r = new InMemoryMcpToolRegistry();
        $r->registerCore(new RegistryName('search_items'), $this->fakeTool());
        $r->register(new RegistryName('acme:custom'), $this->fakeTool());

        self::assertCount(2, $r->names());
    }

    public function testUnregisterRemoves(): void
    {
        $r = new InMemoryMcpToolRegistry();
        $r->register(new RegistryName('acme:custom'), $this->fakeTool());
        $r->unregister(new RegistryName('acme:custom'));

        self::assertFalse($r->has(new RegistryName('acme:custom')));
    }

    private function fakeTool(): McpTool
    {
        return new class () implements McpTool {};
    }
}
