<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

use InvalidArgumentException;

/**
 * Route registration emitted by a plugin to expose an additional
 * `/api/v1/plugins/<vendor>/<path>` endpoint (cf. ADR 0015).
 *
 * The router enforces the `/api/v1/plugins/` prefix when registering
 * plugin routes — plugins cannot collide with core operationIds or
 * mount routes outside their namespace. Path validation here keeps
 * `path` in OpenAPI form (`/items/{id}`); the registry adds the
 * prefix before forwarding to fast-route.
 */
final readonly class PluginRoute
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        public string $method,
        public string $path,
        public string $operationId,
    ) {
        if (!in_array(strtoupper($method), self::ALLOWED_METHODS, true)) {
            throw new InvalidArgumentException(sprintf(
                'PluginRoute.method must be one of %s (got %s).',
                implode('|', self::ALLOWED_METHODS),
                $method,
            ));
        }
        if ($path === '' || $path[0] !== '/') {
            throw new InvalidArgumentException(sprintf(
                'PluginRoute.path must start with "/" (got %s).',
                $path,
            ));
        }
        if ($operationId === '') {
            throw new InvalidArgumentException('PluginRoute.operationId must not be empty.');
        }
    }

    public function methodUpper(): string
    {
        return strtoupper($this->method);
    }
}
