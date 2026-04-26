<?php

declare(strict_types=1);

namespace Saso\Domain\Shared\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by the API router when a path is registered but the requested
 * HTTP method is not. The handler renders a 405 problem with code
 * `SASO-INFRA-9004` and the `allowed` set in the context.
 */
final class MethodNotAllowedException extends DomainException
{
    /**
     * @param list<string> $allowed methods registered for this path
     */
    public static function for(string $method, string $path, array $allowed): self
    {
        sort($allowed);

        return new self(
            ErrorCode::InfraMethodNotAllowed,
            sprintf(
                'Method %s is not allowed on %s. Allowed: %s.',
                strtoupper($method),
                $path,
                implode(', ', $allowed),
            ),
            [
                'method'  => strtoupper($method),
                'path'    => $path,
                'allowed' => $allowed,
            ],
        );
    }

    /**
     * @return list<string>
     */
    public function allowed(): array
    {
        $allowed = $this->context()['allowed'] ?? [];

        return is_array($allowed) ? array_values($allowed) : [];
    }
}
