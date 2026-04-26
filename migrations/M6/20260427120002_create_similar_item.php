<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Creates the `similar_item` table — denormalised cache of
 * "items most similar to item X", populated offline from the
 * OpenSearch k-NN index (cf. ADR 0010 / ADR 0011).
 *
 * The registration form reads this table to surface "did you mean…?"
 * candidates without issuing a vector query on every keystroke.
 * Stale entries are tolerable; the offline job re-runs on item write.
 *
 * `method` distinguishes the basis for the score (image-only,
 * text-only, or hybrid weighted average).
 *
 * Reversible: `down()` drops the table.
 */
final class CreateSimilarItem extends AbstractMigration
{
    public function up(): void
    {
        $this->table('similar_item', [
            'id'          => false,
            'primary_key' => ['item_id', 'similar_to_id', 'method'],
            'engine'      => 'InnoDB',
            'collation'   => 'utf8mb4_unicode_ci',
            'comment'     => 'Denormalised similar-item cache fed by the OpenSearch k-NN sweep (ADR 0011).',
        ])
            ->addColumn('item_id', 'biginteger', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('similar_to_id', 'biginteger', [
                'signed' => false,
                'null'   => false,
            ])
            ->addColumn('method', 'enum', [
                'values' => ['image', 'text', 'hybrid'],
                'null'   => false,
            ])
            ->addColumn('similarity', 'float', [
                'null'    => false,
                'comment' => 'Cosine similarity in [0, 1].',
            ])
            ->addColumn('last_updated', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['item_id', 'similarity'], ['name' => 'idx_similarity'])
            ->create();
    }

    public function down(): void
    {
        $this->table('similar_item')->drop()->update();
    }
}
