<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Extends `item_attribute_definition`:
 * - Adds `show_on_mobile` / `show_on_web` visibility flags.
 * - Adds `multi_select` and `tags` to the `value_type` enum.
 *
 * `multi_select` — predefined list, multiple choices allowed.
 * `tags`         — free-form text tags (comma-separated or JSON array).
 */
final class ExtendAttributeDefinition extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('item_attribute_definition');

        $result = $this->fetchRow(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_NAME = 'item_attribute_definition'
             AND COLUMN_NAME = 'show_on_mobile'
             AND TABLE_SCHEMA = DATABASE()"
        );

        if (!$result) {
            $table
                ->addColumn('show_on_mobile', 'boolean', [
                    'null'    => false,
                    'default' => 1,
                    'after'   => 'sort_order',
                    'comment' => 'Show this field in the mobile registration form.',
                ])
                ->addColumn('show_on_web', 'boolean', [
                    'null'    => false,
                    'default' => 1,
                    'after'   => 'show_on_mobile',
                    'comment' => 'Show this field in the web registration form.',
                ])
                ->update();
        }

        // Extend the value_type enum to include multi_select and tags.
        $this->execute("
            ALTER TABLE item_attribute_definition
            MODIFY COLUMN value_type ENUM('string','int','float','bool','enum','barcode','multi_select','tags')
                NOT NULL
                COMMENT 'Drives form widget + storage column on item_attribute_value.'
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE item_attribute_definition
            MODIFY COLUMN value_type ENUM('string','int','float','bool','enum','barcode')
                NOT NULL
                COMMENT 'Drives form widget + storage column on item_attribute_value.'
        ");

        $result = $this->fetchRow(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_NAME = 'item_attribute_definition'
             AND COLUMN_NAME = 'show_on_mobile'
             AND TABLE_SCHEMA = DATABASE()"
        );

        if ($result) {
            $table = $this->table('item_attribute_definition');
            $table
                ->removeColumn('show_on_mobile')
                ->removeColumn('show_on_web')
                ->update();
        }
    }
}
