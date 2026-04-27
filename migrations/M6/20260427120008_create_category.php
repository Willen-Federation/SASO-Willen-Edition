<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `category` table for hierarchical classification codes.
 *
 * `code`      — Unique uppercase alphanumeric + hyphen code (e.g. FOOD, FOOD-FRESH).
 * `parent_id` — Self-referencing FK; null means root category.
 * `depth`     — Cached depth (0 = root) for fast subtree queries.
 * `sort_order`— Sibling display order.
 * `name_en`   — English label.
 * `name_ja`   — Japanese label.
 *
 * Reversible: `down()` drops the table.
 */
final class CreateCategory extends AbstractMigration
{
    public function up(): void
    {
        $this->table('category')
            ->addColumn('code', 'string', [
                'limit'   => 64,
                'null'    => false,
                'comment' => 'Unique classification code (e.g. FOOD-FRESH).',
            ])
            ->addColumn('parent_id', 'integer', [
                'null'    => true,
                'signed'  => false,
                'comment' => 'Parent category ID; null = root.',
            ])
            ->addColumn('depth', 'integer', [
                'null'    => false,
                'default' => 0,
                'signed'  => false,
                'comment' => 'Cached depth (0 = root).',
            ])
            ->addColumn('sort_order', 'integer', [
                'null'    => false,
                'default' => 0,
                'signed'  => false,
                'comment' => 'Sibling display order.',
            ])
            ->addColumn('name_en', 'string', [
                'limit'   => 200,
                'null'    => false,
                'comment' => 'English category name.',
            ])
            ->addColumn('name_ja', 'string', [
                'limit'   => 200,
                'null'    => false,
                'comment' => 'Japanese category name.',
            ])
            ->addColumn('description', 'text', [
                'null'    => true,
                'comment' => 'Optional description.',
            ])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['code'], ['unique' => true, 'name' => 'uq_category_code'])
            ->addIndex(['parent_id'], ['name' => 'idx_category_parent'])
            ->create();
    }

    public function down(): void
    {
        $this->table('category')->drop()->save();
    }
}
