<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use Saso\Domain\MobileConnect\Exception\DeviceTokenNotFoundException;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * DELETE /api/v1/mobile/tokens/{id}
 *
 * Soft-deletes a device token by setting `revoked = 1`. The row stays in
 * the DB for audit purposes; subsequent requests from that device are
 * rejected with SASO-MOBILE-2005.
 */
final class TokenRevokeController
{
    public function __construct(
        private readonly DeviceTokenRepository $tokens,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $id    = (int) ($request->pathParams['id'] ?? 0);
        $token = $this->tokens->findById($id);

        if ($token === null) {
            throw new DeviceTokenNotFoundException();
        }

        $this->tokens->save($token->revoke());

        return new JsonResponse(status: 204, body: []);
    }
}
