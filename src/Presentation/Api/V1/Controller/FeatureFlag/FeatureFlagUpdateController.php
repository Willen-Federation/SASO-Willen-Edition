<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeInterface;
use InvalidArgumentException;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagAuditRepository;
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
 *
 * When the patch flips `enabled`, an audit row is written so post-mortems
 * can reconstruct who toggled what and when (ADR 0005). The audit row is
 * best-effort: a failure to persist the audit must not block the toggle
 * itself, but we re-raise so operators see it in the response code rather
 * than silently losing history.
 */
final class FeatureFlagUpdateController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
        private readonly ?FeatureFlagAuditRepository $audit = null,
        private readonly ?\Closure $memberIdResolver = null,
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

        $body = $this->parseBody($request);

        try {
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
        } catch (InvalidArgumentException $e) {
            throw InvalidFlagInputException::fromMessage($e->getMessage(), $e);
        }

        $saved = $this->flags->save($updated);

        if ($this->audit !== null && $flag->enabled !== $saved->enabled) {
            $this->audit->record(
                flagKey: $saved->key->toString(),
                oldEnabled: $flag->enabled,
                newEnabled: $saved->enabled,
                changedBy: $this->resolveMemberId(),
                reason: 'Toggled via REST PATCH',
            );
        }

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

    private function resolveMemberId(): string
    {
        if ($this->memberIdResolver !== null) {
            $id = ($this->memberIdResolver)();
            if (is_string($id) && $id !== '') {
                return $id;
            }
        }
        $sessionId = $_SESSION['id'] ?? null;

        return is_string($sessionId) && $sessionId !== '' ? $sessionId : 'admin';
    }
}
