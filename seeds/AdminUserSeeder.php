<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

require_once __DIR__.'/../entity/Member.php';

class AdminUserSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Seeds the Member table with a default admin user for fresh environments.
     * Uses environment variables SEED_ADMIN_ID and SEED_ADMIN_PASSWORD if set,
     * otherwise uses safe defaults: admin_test / test123456.
     *
     * The Member.id constraint requires 8-20 characters, so default credentials
     * respect this constraint.
     */
    public function run(): void
    {
        // Read env vars with safe defaults matching Member.id constraint (8-20 chars)
        $adminId = getenv('SEED_ADMIN_ID') ?: 'admin_test';
        $adminPassword = getenv('SEED_ADMIN_PASSWORD') ?: 'test123456';

        // Hash the password using Member::hashPassword() — requires Entity class
        require_once __DIR__.'/../entity/Member.php';
        $passwordHash = \saso\entity\Member::hashPassword($adminPassword);

        $table = $this->table('member');

        // Check if admin user already exists (idempotency)
        $exists = $this->fetchRow(sprintf("SELECT id FROM member WHERE id = '%s'", addslashes($adminId)));

        if (!$exists) {
            $table->insert([
                'id'         => $adminId,
                'name'       => 'Administrator',
                'password'   => $passwordHash,
                'role'       => 'admin',
                'avatar_url' => null,
                'display_name' => 'Admin User',
                'bio'        => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->saveData();
        }
    }
}
