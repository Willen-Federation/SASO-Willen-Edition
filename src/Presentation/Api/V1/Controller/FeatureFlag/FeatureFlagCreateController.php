<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
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
 */
final class FeatureFlagCreateController
{
    public function __construct(
        private readonly FeatureFlagRepository $flags,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body = $this->parseBody($request);

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $all    = $this->flags->listAll();
        $nextId = count($all) === 0
            ? 1
            : max(array_map(fn (FeatureFlag $f): int => $f->id, $all)) + 1;

        try {
            $key = new FeatureKey($body['key'] ?? '');
        } catch (InvalidArgumentException $e) {
            throw new class ($e->getMessage()) extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

        if ($this->flags->findByKey($key) !== null) {
            throw new class ('A feature flag with that key already exists.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

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

        $saved = $this->flags->save($flag);

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
}
