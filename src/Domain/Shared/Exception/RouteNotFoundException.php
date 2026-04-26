<?php

declare(strict_types=1);

namespace Saso\Domain\Shared\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by the API router when no path/method combination matches the
 * incoming request. The handler renders a 404 problem with code
 * `SASO-INFRA-9003`.
 */
final class RouteNotFoundException extends DomainException
{
    public static function for(string $method, string $path): self
    {
        return new self(
            ErrorCode::InfraRouteNotFound,
            sprintf('No route is registered for %s %s.', strtoupper($method), $path),
            ['method' => strtoupper($method), 'path' => $path],
        );
    }
}
