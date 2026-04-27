<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeInterface;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * PATCH /api/v1/feature-flags/{key}
 *
 * Partial update — only the fields present in the JSON body are applied.
 * Allows toggling `enabled` or `rolloutPercent` without resending the
 * full object, which is the most common operator action from the Flutter
 * admin panel.
 */
final class FeatureFlagUpdateController
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

        $body = $this->parseBody($request);

        $updated = new FeatureFlag(
            id: $flag->id,
            key: $flag->key,
            description: isset($body['description']) ? (string) $body['description'] : $flag->description,
            enabled: isset($body['enabled']) ? (bool) $body['enabled'] : $flag->enabled,
            rolloutPercent: isset($body['rolloutPercent']) ? (int) $body['rolloutPercent'] : $flag->rolloutPercent,
            conditions: array_key_exists('conditions', $body)
                ? (is_array($body['conditions']) ? $body['conditions'] : null)
                : $flag->conditions,
            errorThreshold: isset($body['errorThreshold'])
                ? (int) $body['errorThreshold']
                : $flag->errorThreshold,
            errorWindowMinutes: isset($body['errorWindowMinutes'])
                ? (int) $body['errorWindowMinutes']
                : $flag->errorWindowMinutes,
            autoDisabledAt: $flag->autoDisabledAt,
            autoDisableReason: $flag->autoDisableReason,
            createdAt: $flag->createdAt,
            updatedAt: $flag->updatedAt,
        );

        $saved = $this->flags->save($updated);

        return new JsonResponse(
            status: 200,
            body: [
                'id'                 => $saved->id,
                'key'                => $saved->key->toString(),
                'description'        => $saved->description,
                'enabled'            => $saved->enabled,
                'rolloutPercent'     => $saved->rolloutPercent,
                'conditions'         => $saved->conditions,
                'errorThreshold'     => $saved->errorThreshold,
                'errorWindowMinutes' => $saved->errorWindowMinutes,
                'autoDisabledAt'     => $saved->autoDisabledAt?->format(DateTimeInterface::RFC3339),
                'autoDisableReason'  => $saved->autoDisableReason,
                'createdAt'          => $saved->createdAt->format(DateTimeInterface::RFC3339),
                'updatedAt'          => $saved->updatedAt->format(DateTimeInterface::RFC3339),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function parseBody(HttpRequest $request): array
    {
        if ($request->body === null || $request->body === '') {
            return [];
        }

        $decoded = json_decode($request->body, associative: true);

        return is_array($decoded) ? $decoded : [];
    }
}
