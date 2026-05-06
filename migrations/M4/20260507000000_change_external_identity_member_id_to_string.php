<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeExternalIdentityMemberIdToString extends AbstractMigration
{
    public function up(): void
    {
        $this->table('member_external_identity')
            ->changeColumn('member_id', 'string', [
                'limit' => 20,
                'null' => false,
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('member_external_identity')
            ->changeColumn('member_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->update();
    }
}
