<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Plugin\Registry;

use Saso\Domain\Plugin\Registry\ApiRouteRegistry;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\PluginRoute;
use Saso\Domain\Plugin\Registry\RegistryName;

/**
 * In-process {@see ApiRouteRegistry}. Same reserved-vs-vendor rules
 * as the AI / Auth / MCP registries — bare names are core, vendor
 * names are plugin-owned.
 *
 * Storage is a parallel pair of maps (`PluginRoute` + `callable`)
 * keyed by registration name. The route's `operationId` flows
 * through to the M6-J3 router which appends it to the fast-route
 * dispatcher.
 */
final class InMemoryApiRouteRegistry implements ApiRouteRegistry
{
    private const REGISTRY_LABEL = 'api_route';

    /** @var array<string, PluginRoute> */
    private array $routesByName = [];

    /** @var array<string, callable> */
    private array $handlersByName = [];

    public function register(RegistryName $name, PluginRoute $route, callable $handler): void
    {
        if ($name->isReserved() && isset($this->routesByName[$name->toString()])) {
            throw RegistryCollisionException::for(self::REGISTRY_LABEL, $name);
        }
        $this->routesByName[$name->toString()]   = $route;
        $this->handlersByName[$name->toString()] = $handler;
    }

    public function registerCore(RegistryName $name, PluginRoute $route, callable $handler): void
    {
        $this->routesByName[$name->toString()]   = $route;
        $this->handlersByName[$name->toString()] = $handler;
    }

    public function unregister(RegistryName $name): void
    {
        unset($this->routesByName[$name->toString()], $this->handlersByName[$name->toString()]);
    }

    public function has(RegistryName $name): bool
    {
        return isset($this->routesByName[$name->toString()]);
    }

    public function routes(): array
    {
        return array_values($this->routesByName);
    }

    public function handlerFor(RegistryName $name): ?callable
    {
        return $this->handlersByName[$name->toString()] ?? null;
    }
}
