<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/feature-flags
 *
 * Returns all feature flags ordered by key. No pagination: the number of
 * flags in a typical installation fits comfortably in a single response and
 * Flutter devices cache the full bundle via GET /api/v1/mobile/config.
 */
final class FeatureFlagListController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $list = $this->flags->listAll();

        return new JsonResponse(
            status: 200,
            body: [
                'data'  => array_map(self::serialize(...), $list),
                'total' => count($list),
            ],
        );
    }

    /** @return array<string, mixed> */
    private static function serialize(FeatureFlag $flag): array
    {
        return [
            'id'                 => $flag->id,
            'key'                => $flag->key->toString(),
            'description'        => $flag->description,
            'enabled'            => $flag->enabled,
            'rolloutPercent'     => $flag->rolloutPercent,
            'conditions'         => $flag->conditions,
            'errorThreshold'     => $flag->errorThreshold,
            'errorWindowMinutes' => $flag->errorWindowMinutes,
            'autoDisabledAt'     => $flag->autoDisabledAt?->format(\DateTimeInterface::RFC3339),
            'autoDisableReason'  => $flag->autoDisableReason,
            'createdAt'          => $flag->createdAt->format(\DateTimeInterface::RFC3339),
            'updatedAt'          => $flag->updatedAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
