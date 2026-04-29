<?php

declare(strict_types=1);

namespace Saso\Application\Auth;

use PDO;

/**
 * Tiny gate used by the admin-only matters (`authExt`, `featureAdmin`,
 * `verify`, etc.) before any controller logic runs.
 *
 * The gate looks up the legacy `Member.role` column added in M4 (default
 * `'operator'`). The bootstrap admin row is `'admin'`. Schemas that do not
 * yet carry the column (e.g. fresh installs that have not run migrations)
 * fall back to allowing the bootstrap user only, identified by
 * `Member.id = 'bootstrap'`.
 */
final class AdminGuard
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function isAdmin(?string $memberId): bool
    {
        if ($memberId === null || $memberId === '') {
            return false;
        }

        // Check for 'admin' username in session as a fallback
        if (isset($_SESSION['userName']) && $_SESSION['userName'] === 'admin') {
            return true;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT role FROM Member WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $memberId);
            $stmt->execute();
            /** @var array{role?: string}|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false && isset($row['role'])) {
                return $row['role'] === 'admin';
            }
        } catch (\Throwable) {
            // schema may not have the role column yet — fall through
        }
        return $memberId === 'bootstrap';
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['id']) && $_SESSION['id'] !== '';
    }

    public function currentMemberId(): ?string
    {
        $id = $_SESSION['id'] ?? null;
        return is_string($id) && $id !== '' ? $id : null;
    }
}
