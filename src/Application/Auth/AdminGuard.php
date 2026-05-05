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

    /**
     * Check whether the given member has a specific permission key.
     *
     * Permissions are stored as a JSON array in the Role table.
     * Falls back to isAdmin() when the Role table does not yet exist
     * (e.g. before the migration runs).
     */
    public function hasPermission(?string $memberId, string $permission): bool
    {
        if ($memberId === null || $memberId === '') {
            return false;
        }
        try {
            $stmt = $this->pdo->prepare(
                'SELECT r.permissions
                   FROM Member m
                   LEFT JOIN Role r ON r.name = m.role
                  WHERE m.id = :id
                  LIMIT 1'
            );
            $stmt->bindValue(':id', $memberId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false && isset($row['permissions'])) {
                $perms = json_decode((string) $row['permissions'], true);
                return is_array($perms) && in_array($permission, $perms, true);
            }
        } catch (\Throwable) {
            // Role table not available — fall back to binary admin check
        }
        return $this->isAdmin($memberId);
    }

    /**
     * Return all permission keys granted to the given member, or [] on error.
     *
     * @return list<string>
     */
    public function getPermissions(?string $memberId): array
    {
        if ($memberId === null || $memberId === '') {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare(
                'SELECT r.permissions
                   FROM Member m
                   LEFT JOIN Role r ON r.name = m.role
                  WHERE m.id = :id
                  LIMIT 1'
            );
            $stmt->bindValue(':id', $memberId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false && isset($row['permissions'])) {
                $perms = json_decode((string) $row['permissions'], true);
                return is_array($perms) ? $perms : [];
            }
        } catch (\Throwable) {
        }
        return $this->isAdmin($memberId) ? array_keys(\saso\entity\Role::PERMISSIONS) : [];
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
