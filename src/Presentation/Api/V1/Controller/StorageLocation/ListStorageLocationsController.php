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
 * GET /api/v1/storage-locations
 *
 * Query parameters:
 *   parent_id  int   list children of this node (omit for roots)
 */
final class ListStorageLocationsController
{
    public function __construct(
        private readonly StorageLocationRepository $locations,
        private readonly JwtGuard $guard,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->requireScope($request, 'items:read');

        $parentId = null;
        if (isset($request->query['parent_id']) && $request->query['parent_id'] !== '') {
            $raw = (int) $request->query['parent_id'];
            if ($raw < 1) {
                throw new class ('parent_id must be a positive integer.') extends DomainException {
                    public function __construct(string $msg)
                    {
                        parent::__construct(ErrorCode::MobileInvalidRequest, $msg);
                    }
                };
            }
            $parentId = $raw;
        }

        $list = $parentId !== null
            ? $this->locations->listChildrenOf($parentId)
            : $this->locations->listRoots();

        return new JsonResponse(
            status: 200,
            body: [
                'data'  => array_map(self::serialize(...), $list),
                'total' => count($list),
            ],
        );
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
        ];
    }
}
