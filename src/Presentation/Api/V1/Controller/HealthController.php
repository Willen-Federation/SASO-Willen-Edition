<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * Liveness probe for `/api/v1/health`.
 *
 * The controller answers a single question: "is PHP running and able to
 * respond?". It deliberately does not touch the database, the IdP
 * registry, or the filesystem — readiness checks (which do) live behind
 * `/health/readiness`, introduced in M4.
 */
final class HealthController
{
    /**
     * Pinned in code so the response can be served before the database is
     * reachable. The catalogue version in `config/openapi.yaml` is the
     * authoritative one for SDK generators; this string mirrors it.
     */
    public const VERSION = '1.0.0-alpha';

    public function __construct(
        private readonly DateTimeImmutable $now = new DateTimeImmutable('now', new DateTimeZone('UTC')),
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        return new JsonResponse(
            status: 200,
            body: [
                'status'  => 'ok',
                'version' => self::VERSION,
                'time'    => $this->now->format(DateTimeInterface::RFC3339),
            ],
        );
    }
}
