<?php
namespace saso\shelf;

use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\LocationType;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;
use Saso\Domain\StorageLocation\StorageOperationalStatus;
use DateTimeImmutable;

final class SimpleSaveUsecase
{
    public function __construct(
        private StorageLocationRepository $repository
    ) {
    }

    public function execute(array $data): void
    {
        $areaCode = $data['areaCode'] ?? '';
        $pins = $data['pins'] ?? [];
        $now = new DateTimeImmutable();

        foreach ($pins as $pin) {
            $location = new StorageLocation(
                id: 0, // New
                parentId: null, // For simplicity in this bulk setup, they are roots or handled by areaCode
                code: new LocationCode($pin['code']),
                name: $pin['name'] ?? $pin['code'],
                position: 0,
                depth: 0,
                createdAt: $now,
                updatedAt: $now,
                locationType: LocationType::Shelf,
                description: null,
                capacity: null,
                notes: null,
                operationalStatus: StorageOperationalStatus::Available,
                areaCode: $areaCode,
                mapImageId: null, // To be implemented with image upload
                mapXRatio: (float) $pin['x'],
                mapYRatio: (float) $pin['y']
            );

            $this->repository->save($location);
        }
    }
}
