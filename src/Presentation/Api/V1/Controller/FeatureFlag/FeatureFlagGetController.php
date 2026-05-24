<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeInterface;
use InvalidArgumentException;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/feature-flags/{key}
 */
final class FeatureFlagGetController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $keyStr = $request->pathParams['key'] ?? '';
        try {
            $key = new FeatureKey($keyStr);
        } catch (InvalidArgumentException $e) {
            throw InvalidFlagInputException::fromMessage($e->getMessage(), $e);
        }

        $flag = $this->flags->findByKey($key);

        if ($flag === null) {
            throw FlagNotFoundException::for($key);
        }

        return new JsonResponse(status: 200, body: self::serialize($flag));
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
            'autoDisabledAt'     => $flag->autoDisabledAt?->format(DateTimeInterface::RFC3339),
            'autoDisableReason'  => $flag->autoDisableReason,
            'createdAt'          => $flag->createdAt->format(DateTimeInterface::RFC3339),
            'updatedAt'          => $flag->updatedAt->format(DateTimeInterface::RFC3339),
        ];
    }
}
