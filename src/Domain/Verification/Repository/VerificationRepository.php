<?php

declare(strict_types=1);

namespace Saso\Domain\Verification\Repository;

use Saso\Domain\Verification\VerificationEvent;
use Saso\Domain\Verification\VerificationSession;
use Saso\Domain\Verification\VerificationSummary;

interface VerificationRepository
{
    public function findSessionById(int $id): ?VerificationSession;

    /**
     * @return list<VerificationSession>
     */
    public function listRecentSessions(int $limit = 50, int $offset = 0): array;

    public function saveSession(VerificationSession $session): VerificationSession;

    public function recordEvent(VerificationEvent $event): VerificationEvent;

    /**
     * @return list<VerificationEvent>
     */
    public function listEvents(int $sessionId, int $limit = 500, int $offset = 0): array;

    public function summarise(int $sessionId): VerificationSummary;
}
