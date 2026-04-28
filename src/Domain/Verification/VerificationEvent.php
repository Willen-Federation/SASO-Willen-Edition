<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class VerificationEvent
{
    public function __construct(
        public int $id,
        public int $sessionId,
        public string $scannedCode,
        public ResolvedKind $resolvedKind,
        public ?string $resolvedItemId,
        public ?int $expectedLocationId,
        public ?int $actualLocationId,
        public VerificationResult $result,
        public DateTimeImmutable $scannedAt,
        public ?int $deviceId,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('VerificationEvent.id must be >= 1.');
        }
        if ($sessionId < 1) {
            throw new InvalidArgumentException('VerificationEvent.sessionId must be >= 1.');
        }
        if ($scannedCode === '') {
            throw new InvalidArgumentException('VerificationEvent.scannedCode must not be empty.');
        }
    }
}
