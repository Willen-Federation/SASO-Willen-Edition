<?php

declare(strict_types=1);

namespace Saso\Domain\Messaging\Message;

use InvalidArgumentException;

/**
 * Tells the search-index handler to refresh the OpenSearch document
 * for the given `Item` row (cf. ADR 0010 / ADR 0013).
 *
 * Dispatched from item-write paths (create, update, delete) — the
 * write transaction commits without waiting for the index update,
 * and the handler picks the message up from the queue.
 *
 * `reason` is purely diagnostic — the handler logs it on failure so
 * post-mortems can reconstruct why a particular reindex was queued
 * (e.g. "manual admin button", "post-import sweep").
 */
final readonly class IndexItem implements Message
{
    public function __construct(
        public int $itemId,
        public string $reason = 'item-write',
    ) {
        if ($itemId < 1) {
            throw new InvalidArgumentException('IndexItem.itemId must be a positive integer.');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('IndexItem.reason must not be empty.');
        }
    }
}
