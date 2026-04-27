<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use DateTimeInterface;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/mobile/tokens
 *
 * Lists all device tokens (active, revoked, and expired). Intended for
 * the admin panel so operators can audit which devices are paired and
 * revoke any that should no longer have access.
 *
 * Token hashes are never returned — only the id, deviceName, status, and
 * timestamps needed for display.
 */
final class TokenListController
{
    public function __construct(
        private readonly DeviceTokenRepository $tokens,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $list = $this->tokens->listAll();

        return new JsonResponse(
            status: 200,
            body: [
                'data'  => array_map(self::serialize(...), $list),
                'total' => count($list),
            ],
        );
    }

    /** @return array<string, mixed> */
    private static function serialize(DeviceToken $token): array
    {
        return [
            'id'         => $token->id,
            'deviceName' => $token->deviceName,
            'revoked'    => $token->revoked,
            'lastUsedAt' => $token->lastUsedAt?->format(DateTimeInterface::RFC3339),
            'expiresAt'  => $token->expiresAt->format(DateTimeInterface::RFC3339),
            'createdAt'  => $token->createdAt->format(DateTimeInterface::RFC3339),
        ];
    }
}
