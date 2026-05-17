<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/mobile/config
 *
 * Returns a versioned configuration bundle that Flutter devices download and
 * cache locally. Devices use this bundle to evaluate feature flags without
 * making a round-trip to the server, fulfilling the "offline FeatureFlag"
 * requirement.
 *
 * Bundle shape:
 * {
 *   "version":      "<sha256 of content for cache invalidation>",
 *   "generatedAt":  "<RFC 3339 UTC>",
 *   "featureFlags": [{ "key", "enabled", "rolloutPercent", "conditions" }, ...],
 * }
 *
 * Clients SHOULD store `version` and re-fetch only when it changes.
 * A simple polling strategy: compare the `version` from a lightweight
 * HEAD or GET, then discard if it matches the cached value.
 *
 * Authentication: the device must present a valid Bearer token issued by
 * POST /api/v1/mobile/connect. The controller calls the JwtGuard at the
 * top of `handle()` and aborts with 401 if the token is missing or invalid.
 */
final class ConfigBundleController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
        private readonly JwtGuard $guard,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->requireScope($request, 'feature_flags:read');

        $now      = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $allFlags = $this->flags->listAll();

        $flagsPayload = array_map(
            static fn (FeatureFlag $f): array => [
                'key'            => $f->key->toString(),
                'enabled'        => $f->enabled,
                'rolloutPercent' => $f->rolloutPercent,
                'conditions'     => $f->conditions,
            ],
            $allFlags,
        );

        $content = (string) json_encode($flagsPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $version = hash('sha256', $content);

        return new JsonResponse(
            status: 200,
            body: [
                'version'      => $version,
                'generatedAt'  => $now->format(DateTimeInterface::RFC3339),
                'featureFlags' => $flagsPayload,
            ],
        );
    }
}
