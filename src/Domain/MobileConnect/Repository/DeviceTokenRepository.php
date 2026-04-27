<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Repository;

use Saso\Domain\MobileConnect\DeviceToken;

interface DeviceTokenRepository
{
    public function findByTokenHash(string $hash): ?DeviceToken;

    public function findByRefreshTokenHash(string $hash): ?DeviceToken;

    public function findById(int $id): ?DeviceToken;

    /**
     * @return list<DeviceToken>
     */
    public function listAll(): array;

    public function nextId(): int;

    public function save(DeviceToken $token): DeviceToken;
}
