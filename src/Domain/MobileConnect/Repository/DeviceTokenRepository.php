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

    /**
     * Return every device token owned by a given member, newest first.
     * Used by the MyPage self-service device list — never returns tokens
     * belonging to other members.
     *
     * @return list<DeviceToken>
     */
    public function findByMemberId(string $memberId): array;

    public function nextId(): int;

    public function save(DeviceToken $token): DeviceToken;
}
