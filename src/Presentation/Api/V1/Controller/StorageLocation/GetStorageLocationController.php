<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\StorageLocation;

use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/storage-locations/{id}
 */
final class GetStorageLocationController
{
    public function __construct(
        private readonly StorageLocationRepository $locations,
        private readonly JwtGuard $guard,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->requireScope($request, 'items:read');

        $id = (int) ($request->pathParams['id'] ?? 0);
        if ($id < 1) {
            throw new class ('Invalid storage location ID.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                }
            };
        }

        $loc = $this->locations->findById($id);
        if ($loc === null) {
            throw new class ('Storage location not found.') extends DomainException {
                public function __construct(string $msg)
                {
                    parent::__construct(ErrorCode::LocationNotFound, $msg);
                }
            };
        }

        return new JsonResponse(status: 200, body: self::serialize($loc));
    }

    /** @return array<string, mixed> */
    private static function serialize(StorageLocation $loc): array
    {
        return [
            'id'               => (string) $loc->id,
            'parentId'         => $loc->parentId !== null ? (string) $loc->parentId : null,
            'code'             => $loc->code->toString(),
            'name'             => $loc->name,
            'label'            => $loc->name,
            'location'         => $loc->code->toString(),
            'locationType'     => $loc->locationType->value,
            'depth'            => $loc->depth,
            'position'         => $loc->position,
            'operationalStatus' => $loc->operationalStatus->value,
            'canReceive'       => $loc->operationalStatus->canReceive(),
            'canShip'          => $loc->operationalStatus->canShip(),
            'capacity'         => $loc->capacity,
            'description'      => $loc->description,
            'itemIds'          => [],
        ];
    }
}
