<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Auth;

use DateTimeInterface;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/auth/providers/{id}
 */
final class ProviderGetController
{
    public function __construct(
        private readonly AuthProviderRepository $providers,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $id = (int) ($request->pathParams['id'] ?? 0);
        if ($id <= 0) {
            throw new class ('Invalid provider ID.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

        $record = $this->providers->findById(new AuthProviderId($id));
        if ($record === null) {
            throw new class ('Auth provider not found.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::InfraRouteNotFound, $msg);
                }
            };
        }

        return new JsonResponse(status: 200, body: $this->serialize($record));
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
