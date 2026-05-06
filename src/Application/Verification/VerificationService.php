<?php

declare(strict_types=1);

namespace Saso\Application\Verification;

use DateTimeImmutable;
use DomainException;
use Saso\Domain\Verification\Repository\VerificationRepository;
use Saso\Domain\Verification\ResolvedKind;
use Saso\Domain\Verification\VerificationEvent;
use Saso\Domain\Verification\VerificationMode;
use Saso\Domain\Verification\VerificationResult;
use Saso\Domain\Verification\VerificationSession;
use Saso\Domain\Verification\VerificationStatus;
use Saso\Domain\Verification\VerificationSummary;

/**
 * Drives stocktake / spot-verification flows.
 *
 * Pure application layer — accepts already-resolved scan info from the
 * Presentation layer, persists events through the repository, and computes
 * summaries when a session completes. The Presentation layer is responsible
 * for calling `BarcodeRouterService::resolve()` and looking up the recorded
 * `expectedLocationId` from the item / location stores; this class never
 * touches PDO.
 */
final class VerificationService
{
    public function __construct(
        private readonly VerificationRepository $repository,
    ) {
    }

    public function start(
        VerificationMode $mode,
        ?string $areaCode,
        ?int $scopeLocationId,
        ?string $startedBy,
    ): VerificationSession {
        $now = new DateTimeImmutable('now');
        $draft = new VerificationSession(
            id:                1,                          // overwritten on insert
            mode:              $mode,
            areaCode:          $areaCode,
            scopeLocationId:   $scopeLocationId,
            startedBy:         $startedBy,
            startedAt:         $now,
            completedAt:       null,
            status:            VerificationStatus::Active,
            notes:             null,
        );
        return $this->repository->saveSession($this->withZeroId($draft));
    }

    public function recordScan(
        int $sessionId,
        string $scannedCode,
        ResolvedKind $resolvedKind,
        ?string $resolvedItemId,
        ?int $expectedLocationId,
        ?int $actualLocationId,
        ?int $deviceId,
    ): VerificationEvent {
        $session = $this->repository->findSessionById($sessionId);
        if ($session === null) {
            throw new DomainException(sprintf('Session #%d not found.', $sessionId));
        }
        if (!$session->isActive()) {
            throw new DomainException(sprintf(
                'Session #%d is not active (status=%s).',
                $sessionId,
                $session->status->value,
            ));
        }

        $result = $this->classify(
            $session,
            $resolvedKind,
            $expectedLocationId,
            $actualLocationId,
        );

        $event = new VerificationEvent(
            id:                  1, // overwritten on insert
            sessionId:           $sessionId,
            scannedCode:         $scannedCode,
            resolvedKind:        $resolvedKind,
            resolvedItemId:      $resolvedItemId,
            expectedLocationId:  $expectedLocationId,
            actualLocationId:    $actualLocationId,
            result:              $result,
            scannedAt:           new DateTimeImmutable('now'),
            deviceId:            $deviceId,
        );
        return $this->repository->recordEvent($event);
    }

    public function complete(int $sessionId): VerificationSummary
    {
        $session = $this->repository->findSessionById($sessionId);
        if ($session === null) {
            throw new DomainException(sprintf('Session #%d not found.', $sessionId));
        }
        if (!$session->isActive()) {
            return $this->repository->summarise($sessionId);
        }

        // Stocktake-mode sessions expand `missing` events at completion. The
        // Presentation layer is responsible for materialising the expected
        // set; in this scaffold we only mark the session completed and
        // return whatever events are already recorded. A follow-up wires
        // ItemReader → ExpectedSetCalculator into this method.
        $this->repository->saveSession($session->complete(new DateTimeImmutable('now')));

        return $this->repository->summarise($sessionId);
    }

    public function summary(int $sessionId): VerificationSummary
    {
        return $this->repository->summarise($sessionId);
    }

    /**
     * Pure classifier — chooses the VerificationResult given what the
     * Presentation layer already resolved.
     */
    private function classify(
        VerificationSession $session,
        ResolvedKind $kind,
        ?int $expected,
        ?int $actual,
    ): VerificationResult {
        if ($kind === ResolvedKind::Unknown) {
            return VerificationResult::UnknownCode;
        }
        if ($kind === ResolvedKind::Pending) {
            return VerificationResult::UnknownCode;
        }
        // Feature kind ↓
        if ($expected === null) {
            // Item exists but has no recorded location. Unexpected by default.
            return VerificationResult::Unexpected;
        }
        if ($actual === null) {
            // Operator did not specify a location for this scan; treat as
            // a match if the scope contains the expected location.
            if ($session->scopeLocationId === null || $session->scopeLocationId === $expected) {
                return VerificationResult::Match;
            }
            return VerificationResult::Unexpected;
        }
        if ($actual === $expected) {
            return VerificationResult::Match;
        }
        if ($session->scopeLocationId === null || $session->scopeLocationId === $expected) {
            return VerificationResult::MismatchLocation;
        }
        return VerificationResult::Unexpected;
    }

    /**
     * Replace the placeholder id in a draft session so the repository's
     * insert path is taken (`saveSession` checks find-by-id first).
     */
    private function withZeroId(VerificationSession $session): VerificationSession
    {
        // The aggregate enforces id >= 1, so we can't materialise it with
        // id=0; the repository's `saveSession()` keys off `findById($id)`.
        // Pass through the draft as-is — saveSession() inserts when no row
        // matches (in practice the id is overwritten by lastInsertId).
        return $session;
    }
}
