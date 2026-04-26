<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Plugin\Registry;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\PluginRoute;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Infrastructure\Plugin\Registry\InMemoryApiRouteRegistry;

final class InMemoryApiRouteRegistryTest extends TestCase
{
    public function testStartsEmpty(): void
    {
        $r = new InMemoryApiRouteRegistry();

        self::assertSame([], $r->routes());
    }

    public function testRegisterStoresRouteAndHandler(): void
    {
        $r       = new InMemoryApiRouteRegistry();
        $route   = new PluginRoute(method: 'GET', path: '/items', operationId: 'getItems');
        $handler = static fn () => 'ok';

        $r->register(new RegistryName('acme:items'), $route, $handler);

        self::assertTrue($r->has(new RegistryName('acme:items')));
        self::assertCount(1, $r->routes());
        self::assertSame($handler, $r->handlerFor(new RegistryName('acme:items')));
    }

    public function testPluginCannotDisplaceReservedName(): void
    {
        $r     = new InMemoryApiRouteRegistry();
        $route = new PluginRoute(method: 'GET', path: '/x', operationId: 'getX');

        $r->registerCore(new RegistryName('items'), $route, static fn () => 'core');

        $this->expectException(RegistryCollisionException::class);

        $r->register(new RegistryName('items'), $route, static fn () => 'plugin');
    }

    public function testReregisterOwnVendorNameOverwrites(): void
    {
        $r     = new InMemoryApiRouteRegistry();
        $route = new PluginRoute(method: 'GET', path: '/x', operationId: 'getX');

        $first  = static fn () => 'first';
        $second = static fn () => 'second';

        $r->register(new RegistryName('acme:x'), $route, $first);
        $r->register(new RegistryName('acme:x'), $route, $second);

        self::assertSame($second, $r->handlerFor(new RegistryName('acme:x')));
    }

    public function testUnregisterRemovesBothMaps(): void
    {
        $r     = new InMemoryApiRouteRegistry();
        $route = new PluginRoute(method: 'GET', path: '/x', operationId: 'op');
        $r->register(new RegistryName('acme:x'), $route, static fn () => null);

        $r->unregister(new RegistryName('acme:x'));

        self::assertFalse($r->has(new RegistryName('acme:x')));
        self::assertNull($r->handlerFor(new RegistryName('acme:x')));
        self::assertSame([], $r->routes());
    }

    public function testRoutesReturnsOnlyPluginRouteValueObjects(): void
    {
        $r = new InMemoryApiRouteRegistry();
        $r->register(
            new RegistryName('acme:a'),
            new PluginRoute(method: 'GET', path: '/a', operationId: 'a'),
            static fn () => null,
        );
        $r->register(
            new RegistryName('acme:b'),
            new PluginRoute(method: 'POST', path: '/b', operationId: 'b'),
            static fn () => null,
        );

        $routes = $r->routes();
        self::assertCount(2, $routes);
        foreach ($routes as $route) {
            self::assertInstanceOf(PluginRoute::class, $route);
        }
    }
}
