<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates `messenger_messages` — Doctrine Messenger transport table.
 *
 * Schema matches the standard Symfony Doctrine transport schema
 * (symfony/doctrine-messenger ≥ 6.2).  Messages persist across
 * worker restarts; the Doctrine transport polls this table.
 */
final class CreateMessengerMessagesTable extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('messenger_messages')) {
            return;
        }

        $this->execute('
            CREATE TABLE messenger_messages (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                body            LONGTEXT        NOT NULL,
                headers         LONGTEXT        NOT NULL,
                queue_name      VARCHAR(190)    NOT NULL,
                created_at      DATETIME        NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                available_at    DATETIME        NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                delivered_at    DATETIME        DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY (id),
                INDEX IDX_75EA56E0FB7336F0 (queue_name),
                INDEX IDX_75EA56E0E3BD61CE (available_at),
                INDEX IDX_75EA56E016BA31DB (delivered_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function down(): void
    {
        $this->table('messenger_messages')->drop()->update();
    }
}
