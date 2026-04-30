<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Auth;

use DateTimeInterface;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/auth/providers
 *
 * Returns all registered auth providers (admin only).
 * Secrets are never included in responses.
 */
final class ProviderListController
{
    public function __construct(
        private readonly AuthProviderRepository $providers,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $list = $this->providers->listAll();

        return new JsonResponse(
            status: 200,
            body: [
                'data'  => array_map([$this, 'serialize'], $list),
                'total' => count($list),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function serialize(AuthProviderRecord $r): array
    {
        return [
            'id'                   => $r->id->value,
            'name'                 => $r->name,
            'type'                 => $r->type->value,
            'issuerOrMetadataUrl'  => $r->issuerOrMetadataUrl,
            'clientId'             => $r->clientId,
            'hasSecret'            => $r->clientSecret !== null && $r->clientSecret !== '',
            'scopes'               => $r->scopes,
            'claimMapping'         => $r->claimMapping,
            'enabled'              => $r->enabled,
            'isDefault'            => $r->isDefault,
            'createdAt'            => $r->createdAt->format(DateTimeInterface::RFC3339),
            'updatedAt'            => $r->updatedAt->format(DateTimeInterface::RFC3339),
        ];
    }
}
