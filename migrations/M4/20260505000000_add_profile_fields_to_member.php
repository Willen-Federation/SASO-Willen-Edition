<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds profile fields to Member table for the new "mypage" (user profile)
 * feature.
 *
 * - avatar_url: External URL (Gravatar, etc) for user avatar. Null defaults
 *   to Bootstrap Icons bi-person-circle.
 * - display_name: User's preferred display name (distinct from login ID).
 *   Null defaults to the login name.
 * - bio: Short user biography. Null allowed (optional).
 * - updated_at: Tracks profile modification time.
 *
 * Reversible.
 */
final class AddProfileFieldsToMember extends AbstractMigration
{
    public function up(): void
    {
        $this->table('Member')
            ->addColumn('avatar_url', 'string', [
                'limit'   => 500,
                'null'    => true,
                'comment' => 'External URL for user avatar (Gravatar, etc). Null = default icon.',
            ])
            ->addColumn('display_name', 'string', [
                'limit'   => 100,
                'null'    => true,
                'comment' => 'User preferred display name, distinct from login id.',
            ])
            ->addColumn('bio', 'text', [
                'null'    => true,
                'comment' => 'User biography / about me (max 500 chars).',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null'    => true,
                'comment' => 'Profile last modification time.',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('Member')
            ->removeColumn('avatar_url')
            ->removeColumn('display_name')
            ->removeColumn('bio')
            ->removeColumn('updated_at')
            ->update();
    }
}
