<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds `Member.role` so the new admin matters (`authExt`, `featureAdmin`,
 * `verify`, …) can gate access without inventing a parallel permissions
 * model.
 *
 * Two roles for now — `operator` (default) and `admin`. Future granular
 * scoping (per-feature read/write) layers on a separate JSON column without
 * breaking this enum: existing rows with `role='admin'` retain full access
 * regardless of the JSON.
 *
 * The bootstrap admin (`Member.id='bootstrap'`) is upgraded to `admin`
 * automatically so the post-install operator can reach the new admin
 * screens. Every other existing row stays at `operator`; an admin can
 * elevate them through the upcoming user-management screen.
 *
 * Reversible.
 */
final class AddRoleToMember extends AbstractMigration
{
    public function up(): void
    {
        $this->table('Member')
            ->addColumn('role', 'string', [
                'limit'   => 16,
                'null'    => false,
                'default' => 'operator',
                'comment' => 'admin or operator. Used by AdminGuard to gate management screens.',
            ])
            ->addIndex(['role'], ['name' => 'idx_role'])
            ->update();

        // Promote the bootstrap row if it exists. Other migrations create
        // bootstrap; here we just defensively elevate it.
        $this->execute("UPDATE Member SET role = 'admin' WHERE id = 'bootstrap'");
    }

    public function down(): void
    {
        $this->table('Member')
            ->removeIndexByName('idx_role')
            ->removeColumn('role')
            ->update();
    }
}
