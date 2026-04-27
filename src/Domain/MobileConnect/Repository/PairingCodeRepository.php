<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Repository;

use Saso\Domain\MobileConnect\PairingCode;

interface PairingCodeRepository
{
    public function findByTokenHash(string $hash): ?PairingCode;

    public function nextId(): int;

    public function save(PairingCode $code): PairingCode;

    public function deleteExpired(): int;
}
