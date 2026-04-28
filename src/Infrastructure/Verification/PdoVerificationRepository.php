<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Verification;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Verification\Repository\VerificationRepository;
use Saso\Domain\Verification\ResolvedKind;
use Saso\Domain\Verification\VerificationEvent;
use Saso\Domain\Verification\VerificationMode;
use Saso\Domain\Verification\VerificationResult;
use Saso\Domain\Verification\VerificationSession;
use Saso\Domain\Verification\VerificationStatus;
use Saso\Domain\Verification\VerificationSummary;

final class PdoVerificationRepository implements VerificationRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findSessionById(int $id): ?VerificationSession
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, mode, area_code, scope_location_id, started_by, started_at, '
            .'completed_at, status, notes FROM verification_session WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, scalar|null>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrateSession($row);
    }

    public function listRecentSessions(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, mode, area_code, scope_location_id, started_by, started_at, '
            .'completed_at, status, notes FROM verification_session '
            .'ORDER BY started_at DESC, id DESC LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        /** @var list<array<string, scalar|null>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->hydrateSession($row);
        }
        return $out;
    }

    public function saveSession(VerificationSession $session): VerificationSession
    {
        if ($session->id > 0 && $this->findSessionById($session->id) !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE verification_session SET completed_at = :ca, status = :status, notes = :notes '
                .'WHERE id = :id'
            );
            $stmt->bindValue('ca', $session->completedAt?->format('Y-m-d H:i:s'), $session->completedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('status', $session->status->value);
            $stmt->bindValue('notes', $session->notes, $session->notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('id', $session->id, PDO::PARAM_INT);
            $stmt->execute();
            return $session;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO verification_session '
            .'(mode, area_code, scope_location_id, started_by, started_at, completed_at, status, notes) '
            .'VALUES (:mode, :area, :scope, :by, :sa, :ca, :status, :notes)'
        );
        $stmt->bindValue('mode',   $session->mode->value);
        $stmt->bindValue('area',   $session->areaCode, $session->areaCode === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('scope',  $session->scopeLocationId, $session->scopeLocationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('by',     $session->startedBy, $session->startedBy === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('sa',     $session->startedAt->format('Y-m-d H:i:s'));
        $stmt->bindValue('ca',     $session->completedAt?->format('Y-m-d H:i:s'), $session->completedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('status', $session->status->value);
        $stmt->bindValue('notes',  $session->notes, $session->notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return new VerificationSession(
            id:               (int) $this->pdo->lastInsertId(),
            mode:             $session->mode,
            areaCode:         $session->areaCode,
            scopeLocationId:  $session->scopeLocationId,
            startedBy:        $session->startedBy,
            startedAt:        $session->startedAt,
            completedAt:      $session->completedAt,
            status:           $session->status,
            notes:            $session->notes,
        );
    }

    public function recordEvent(VerificationEvent $event): VerificationEvent
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO verification_event '
            .'(session_id, scanned_code, resolved_kind, resolved_item_id, expected_location_id, '
            .' actual_location_id, result, scanned_at, device_id) '
            .'VALUES (:s, :code, :kind, :item, :expected, :actual, :result, :at, :dev)'
        );
        $stmt->bindValue('s',        $event->sessionId, PDO::PARAM_INT);
        $stmt->bindValue('code',     $event->scannedCode);
        $stmt->bindValue('kind',     $event->resolvedKind->value);
        $stmt->bindValue('item',     $event->resolvedItemId, $event->resolvedItemId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('expected', $event->expectedLocationId, $event->expectedLocationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('actual',   $event->actualLocationId, $event->actualLocationId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('result',   $event->result->value);
        $stmt->bindValue('at',       $event->scannedAt->format('Y-m-d H:i:s'));
        $stmt->bindValue('dev',      $event->deviceId, $event->deviceId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        return new VerificationEvent(
            id:                  (int) $this->pdo->lastInsertId(),
            sessionId:           $event->sessionId,
            scannedCode:         $event->scannedCode,
            resolvedKind:        $event->resolvedKind,
            resolvedItemId:      $event->resolvedItemId,
            expectedLocationId:  $event->expectedLocationId,
            actualLocationId:    $event->actualLocationId,
            result:              $event->result,
            scannedAt:           $event->scannedAt,
            deviceId:            $event->deviceId,
        );
    }

    public function listEvents(int $sessionId, int $limit = 500, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, session_id, scanned_code, resolved_kind, resolved_item_id, '
            .' expected_location_id, actual_location_id, result, scanned_at, device_id '
            .' FROM verification_event WHERE session_id = :s '
            .' ORDER BY scanned_at DESC, id DESC LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue('s',   $sessionId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        /** @var list<array<string, scalar|null>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->hydrateEvent($row);
        }
        return $out;
    }

    public function summarise(int $sessionId): VerificationSummary
    {
        $stmt = $this->pdo->prepare(
            "SELECT result, COUNT(*) AS n FROM verification_event "
            ."WHERE session_id = :s GROUP BY result"
        );
        $stmt->bindValue('s', $sessionId, PDO::PARAM_INT);
        $stmt->execute();
        /** @var list<array{result: string, n: int|string}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = ['match' => 0, 'missing' => 0, 'unexpected' => 0, 'mismatch_location' => 0, 'unknown_code' => 0];
        foreach ($rows as $r) {
            $counts[(string) $r['result']] = (int) $r['n'];
        }
        $expected = $counts['match'] + $counts['missing'] + $counts['mismatch_location'];
        return new VerificationSummary(
            sessionId:              $sessionId,
            expectedCount:          $expected,
            matchCount:             $counts['match'],
            missingCount:           $counts['missing'],
            unexpectedCount:        $counts['unexpected'],
            mismatchLocationCount:  $counts['mismatch_location'],
            unknownCodeCount:       $counts['unknown_code'],
        );
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function hydrateSession(array $row): VerificationSession
    {
        return new VerificationSession(
            id:               (int) $row['id'],
            mode:             VerificationMode::from((string) $row['mode']),
            areaCode:         isset($row['area_code']) ? (string) $row['area_code'] : null,
            scopeLocationId:  isset($row['scope_location_id']) ? (int) $row['scope_location_id'] : null,
            startedBy:        isset($row['started_by']) ? (string) $row['started_by'] : null,
            startedAt:        new DateTimeImmutable((string) $row['started_at'], $this->timezone),
            completedAt:      isset($row['completed_at']) ? new DateTimeImmutable((string) $row['completed_at'], $this->timezone) : null,
            status:           VerificationStatus::from((string) $row['status']),
            notes:            isset($row['notes']) ? (string) $row['notes'] : null,
        );
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function hydrateEvent(array $row): VerificationEvent
    {
        return new VerificationEvent(
            id:                  (int) $row['id'],
            sessionId:           (int) $row['session_id'],
            scannedCode:         (string) $row['scanned_code'],
            resolvedKind:        ResolvedKind::from((string) $row['resolved_kind']),
            resolvedItemId:      isset($row['resolved_item_id']) ? (string) $row['resolved_item_id'] : null,
            expectedLocationId:  isset($row['expected_location_id']) ? (int) $row['expected_location_id'] : null,
            actualLocationId:    isset($row['actual_location_id']) ? (int) $row['actual_location_id'] : null,
            result:              VerificationResult::from((string) $row['result']),
            scannedAt:           new DateTimeImmutable((string) $row['scanned_at'], $this->timezone),
            deviceId:            isset($row['device_id']) ? (int) $row['device_id'] : null,
        );
    }
}
