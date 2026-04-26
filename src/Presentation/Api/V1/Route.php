<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

/**
 * One row of the OpenAPI dispatch table.
 *
 * `path` is in OpenAPI form (`/items/{id}`); `method` is upper-case
 * (`GET`, `POST`, …); `operationId` keys into the handler map registered
 * with the {@see Router}.
 */
final readonly class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public string $operationId,
    ) {
    }
}
