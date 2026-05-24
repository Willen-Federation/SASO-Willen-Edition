<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagAuditRepository;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * POST /api/v1/feature-flags
 *
 * Body (JSON):
 *   key                  string  required, 1-120 chars [a-z0-9._]
 *   description          string  required
 *   enabled              bool    optional, default false
 *   rolloutPercent       int     optional, 0-100, default 0
 *   conditions           object  optional
 *   errorThreshold       int     optional, ≥0, default 0
 *   errorWindowMinutes   int     optional, ≥1, default 60
 *
 * Creating a flag with `enabled = true` writes a `false → true` audit row
 * so the flag's history shows the bootstrap transition.
 */
final class FeatureFlagCreateController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
        private readonly ?FeatureFlagAuditRepository $audit = null,
        private readonly ?\Closure $memberIdResolver = null,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body = $this->parseBody($request);

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            $key = new FeatureKey((string) ($body['key'] ?? ''));
        } catch (InvalidArgumentException $e) {
            throw InvalidFlagInputException::fromMessage($e->getMessage(), $e);
        }

        // Validate optional `conditions` shape early — the FeatureFlag
        // constructor only enforces it is array|null. Pass-through of
        // arbitrary nested JSON is intentional, but reject scalars/strings
        // up-front so the operator sees a 400 instead of a silent drop to
        // null (which is what casting to array would do).
        if (isset($body['conditions']) && !is_array($body['conditions'])) {
            throw InvalidFlagInputException::fromMessage(
                'FeatureFlag.conditions must be a JSON object or null.',
            );
        }

        if ($this->flags->findByKey($key) !== null) {
            throw InvalidFlagInputException::fromMessage(
                sprintf('A feature flag with key "%s" already exists.', $key->toString()),
            );
        }

        $nextId = $this->flags->nextId();

        try {
            $flag = new FeatureFlag(
                id: $nextId,
                key: $key,
                description: (string) ($body['description'] ?? ''),
                enabled: (bool) ($body['enabled'] ?? false),
                rolloutPercent: (int) ($body['rolloutPercent'] ?? 0),
                conditions: isset($body['conditions']) && is_array($body['conditions'])
                    ? $body['conditions']
                    : null,
                errorThreshold: (int) ($body['errorThreshold'] ?? 0),
                errorWindowMinutes: (int) ($body['errorWindowMinutes'] ?? 60),
                autoDisabledAt: null,
                autoDisableReason: null,
                createdAt: $now,
                updatedAt: $now,
            );
        } catch (InvalidArgumentException $e) {
            throw InvalidFlagInputException::fromMessage($e->getMessage(), $e);
        }

        $saved = $this->flags->save($flag);

        if ($this->audit !== null && $saved->enabled) {
            // Record bootstrap transition (no previous row → enabled).
            $this->audit->record(
                flagKey: $saved->key->toString(),
                oldEnabled: false,
                newEnabled: true,
                changedBy: $this->resolveMemberId(),
                reason: 'Flag created (enabled) via REST POST',
            );
        }

        return new JsonResponse(
            status: 201,
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
