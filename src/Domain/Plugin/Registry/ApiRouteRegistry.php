<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

/**
 * Plugin extension point for adding new `/api/v1/plugins/*` routes
 * (cf. ADR 0015 + ADR 0002).
 *
 * Plugins call {@see register()} to bind a `PluginRoute` to a
 * handler callable. The M6-J3 boot loop walks the registry and
 * appends the routes to the fast-route dispatcher built from
 * `config/openapi.yaml` — plugin routes never appear in the core
 * spec.
 *
 * `RegistryName` here identifies the registration slot (typically
 * the plugin package's vendor + a slug like `acme:report-export`)
 * so the same plugin can replace its own route on hot-reload while
 * other plugins' routes remain untouched.
 */
interface ApiRouteRegistry
{
    /**
     * @param callable $handler invoked with the request envelope
     *
     * @throws \Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException
     */
    public function register(RegistryName $name, PluginRoute $route, callable $handler): void;

    public function unregister(RegistryName $name): void;

    public function has(RegistryName $name): bool;

    /**
     * @return list<PluginRoute>
     */
    public function routes(): array;

    public function handlerFor(RegistryName $name): ?callable;
}
