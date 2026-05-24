<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\FeatureFlag;

use InvalidArgumentException;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagAuditRepository;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * DELETE /api/v1/feature-flags/{key}
 *
 * Hard-deletes the flag row. The Flutter config bundle will no longer
 * include this flag on the next poll. Audit history in `feature_flag_audit`
 * is preserved (the audit table denormalises `flag_key`) so post-mortems
 * can still reconstruct the flag's history after deletion.
 *
 * When the flag was `enabled = true` at the moment of deletion we record a
 * final audit entry — anything reading the flag right after the delete
 * effectively saw it flip from `enabled` to "gone", and the audit trail
 * should reflect that transition.
 */
final class FeatureFlagDeleteController
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

        $this->flags->delete($flag->id);

        if ($this->audit !== null) {
            $this->audit->record(
                flagKey: $flag->key->toString(),
                oldEnabled: $flag->enabled,
                newEnabled: false,
                changedBy: $this->resolveMemberId(),
                reason: 'Flag deleted via REST DELETE',
            );
        }

        return new JsonResponse(status: 204, body: []);
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
