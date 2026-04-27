<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * DELETE /api/v1/feature-flags/{key}
 *
 * Hard-deletes the flag row. The Flutter config bundle will no longer
 * include this flag on the next poll. Audit history in `feature_flag_audit`
 * is preserved unless a separate cleanup job removes it.
 */
final class FeatureFlagDeleteController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $keyStr = $request->pathParams['key'] ?? '';
        $flag   = $this->flags->findByKey(new FeatureKey($keyStr));

        if ($flag === null) {
            throw FlagNotFoundException::for(new FeatureKey($keyStr));
        }

        $this->flags->delete($flag->id);

        return new JsonResponse(status: 204, body: []);
    }
}
